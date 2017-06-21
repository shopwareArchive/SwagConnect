<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Subscribers;

use Enlight_Event_EventManager;
use Shopware\Components\Model\ModelManager;
use Shopware\Connect\Struct\CheckResult;
use Shopware\Connect\Struct\Message;
use Shopware\Connect\Struct\Order;
use Shopware\Connect\Struct\OrderItem;
use Shopware\Connect\Struct\Product;
use Shopware\Connect\Struct\Reservation;
use Shopware\Models\Order\Status;
use ShopwarePlugins\Connect\Components\ConnectFactory;
use ShopwarePlugins\Connect\Components\Exceptions\CheckoutException;
use ShopwarePlugins\Connect\Components\Logger;
use ShopwarePlugins\Connect\Components\Utils\ConnectOrderUtil;
use ShopwarePlugins\Connect\Components\Utils\OrderPaymentMapper;

/**
 * Handles the whole checkout manipulation, which is required for the connect checkout
 *
 * Class Checkout
 */
class Checkout extends BaseSubscriber
{
    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var string
     */
    private $newSessionId;

    /**
     * @var ConnectFactory
     */
    protected $factory;

    /**
     * @var ModelManager
     */
    protected $manager;

    /**
     * @var Enlight_Event_EventManager
     */
    protected $eventManager;

    /**
     * @param ModelManager $manager
     */
    public function __construct(
        ModelManager $manager,
        Enlight_Event_EventManager $eventManager
    ) {
        parent::__construct();

        $this->manager = $manager;
        $this->eventManager = $eventManager;
    }

    public function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Action_PostDispatch_Frontend_Checkout' => 'fixBasketForConnect',
            'Enlight_Controller_Action_PreDispatch_Frontend_Checkout' => 'reserveConnectProductsOnCheckoutFinish',
            'Shopware_Modules_Admin_Regenerate_Session_Id' => 'updateSessionId',
        ];
    }

    public function updateSessionId(\Enlight_Event_EventArgs $args)
    {
        $this->newSessionId = $args->get('newSessionId');
    }

    /**
     * @return Logger
     */
    public function getLogger()
    {
        if (!$this->logger) {
            $this->logger = new Logger(Shopware()->Db());
        }

        return $this->logger;
    }

    protected function getFactory()
    {
        if ($this->factory === null) {
            $this->factory = new ConnectFactory();
        }

        return $this->factory;
    }

    protected function getCountryCode()
    {
        $countryCodeUtil = $this->getFactory()->getCountryCodeResolver();

        return $countryCodeUtil->getIso3CountryCode();
    }

    /**
     * Event listener method for the checkout confirm- and cartAction.
     *
     *
     * @param \Enlight_Event_EventArgs $args
     *
     * @throws CheckoutException
     */
    public function fixBasketForConnect(\Enlight_Event_EventArgs $args)
    {
        /** @var $action \Enlight_Controller_Action */
        $action = $args->getSubject();
        $view = $action->View();
        $request = $action->Request();
        $actionName = $request->getActionName();
        $sessionId = Shopware()->SessionID();

        $userId = Shopware()->Session()->sUserId;
        $hasConnectProduct = $this->getHelper()->hasBasketConnectProducts($sessionId, $userId);

        if ($hasConnectProduct === false && $this->newSessionId) {
            $hasConnectProduct = $this->getHelper()->hasBasketConnectProducts($this->newSessionId);
        }

        $view->hasConnectProduct = $hasConnectProduct;

        if ($actionName == 'ajax_add_article') {
            $this->registerMyTemplateDir();
            $view->extendsTemplate('frontend/connect/ajax_add_article.tpl');
        }

        // send order to connect
        // this method must be called after external payments (Sofort, Billsafe)
        if ($actionName == 'finish' && !empty($view->sOrderNumber)) {
            try {
                $this->checkoutReservedProducts($view->sOrderNumber);
            } catch (CheckoutException $e) {
                $this->setOrderStatusError($view->sOrderNumber);
                throw $e;
            }
        }

        // clear connect reserved products
        // sometimes with external payment methods
        // $hasConnectProduct will be false, because order is already finished
        // and information about connect products is not available.
        if (!$hasConnectProduct) {
            $this->getHelper()->clearConnectReservation();

            return;
        }

        if (!in_array($actionName, ['confirm', 'shippingPayment', 'cart', 'finish'])) {
            return;
        }

        if (empty($view->sBasket) || !$request->isDispatched()) {
            return;
        }

        if (!empty($view->sOrderNumber)) {
            return;
        }

        if (Shopware()->Config()->get('requirePhoneField')) {
            $this->enforcePhoneNumber($view);
        }

        $this->registerMyTemplateDir();
        if ($this->Application()->Container()->get('shop')->getTemplate()->getVersion() < 3) {
            $view->extendsTemplate('frontend/connect/checkout.tpl');
        }

        $sdk = $this->getSDK();

        // Wrap the basket array in order to make it some more readable
        $basketHelper = $this->getBasketHelper();
        $basketHelper->setBasket($view->sBasket);

        // If no messages are shown, yet, check products from remote shop and build message array
        if (($connectMessages = Shopware()->Session()->connectMessages) === null) {
            $connectMessages = [];

            $session = Shopware()->Session();
            $userData = $session['sOrderVariables']['sUserData'];
            // prepare an order to check products
            $order = new \Shopware\Connect\Struct\Order();
            $order->orderItems = [];
            $order->billingAddress = $order->deliveryAddress = $this->getDeliveryAddress($userData);

            $allProducts = [];

            foreach ($basketHelper->getConnectProducts() as $shopId => $products) {
                $products = $this->getHelper()->prepareConnectUnit($products);
                $allProducts = array_merge($allProducts, $products);
                // add order items in connect order
                $order->orderItems = array_map(function (Product $product) use ($basketHelper) {
                    return new OrderItem([
                        'product' => $product,
                        'count' => $basketHelper->getQuantityForProduct($product),
                    ]);
                }, $products);
            }

            $this->Application()->Container()->get('events')->notify(
                'Connect_Merchant_Create_Order_Before',
                [
                    //we use clone to not be able to modify the connect order
                    'order' => clone $order,
                    'basket' => $view->sBasket,
                ]
            );

            /* @var $checkResult \Shopware\Connect\Struct\CheckResult */
            try {
                $checkResult = $sdk->checkProducts($order);
                $basketHelper->setCheckResult($checkResult);

                if ($checkResult->hasErrors()) {
                    $connectMessages = $checkResult->errors;
                }
            } catch (\Exception $e) {
                $this->getLogger()->write(true, 'Error during checkout', $e, 'checkout');
                // If the checkout results in an exception because the remote shop is not available
                // don't show the exception to the user but tell him to remove the products from that shop
                $connectMessages = $this->getNotAvailableMessageForProducts($allProducts);
            }
        }

        if ($connectMessages) {
            $connectMessages = $this->translateConnectMessages($connectMessages);
        }

        Shopware()->Session()->connectMessages = null;

        // If no products are bought from the local shop, move the first connect shop into
        // the content section. Also set that shop's id in the template
        $shopId = $basketHelper->fixBasket();
        if ($shopId) {
            $view->shopId = $shopId;
        }
        // Increase amount and shipping costs by the amount of connect shipping costs
        $basketHelper->recalculate($basketHelper->getCheckResult());

        $connectMessages = $this->getNotShippableMessages($basketHelper->getCheckResult(), $connectMessages);

        $view->assign($basketHelper->getDefaultTemplateVariables());

        // Set the sOrderVariables for the session based on the original content subarray of the basket array
        // @HL - docs?
        if ($actionName == 'confirm') {
            $session = Shopware()->Session();
            /** @var $variables \ArrayObject */
            $variables = $session->offsetGet('sOrderVariables');

            $session->offsetSet('sOrderVariables', $basketHelper->getOrderVariablesForSession($variables));
        }

        $view->assign($basketHelper->getConnectTemplateVariables($connectMessages));
        $view->assign('showShippingCostsSeparately', $this->getFactory()->getConfigComponent()->getConfig('showShippingCostsSeparately', false));
    }

    /**
     * Helper to translate connect messages from the SDK. Will use the normalized message itself as namespace key
     *
     * @param $connectMessages
     *
     * @return mixed
     */
    private function translateConnectMessages($connectMessages)
    {
        $namespace = Shopware()->Snippets()->getNamespace('frontend/checkout/connect');

        foreach ($connectMessages as &$connectMessage) {
            $message = trim($connectMessage->message);
            $normalized = strtolower(preg_replace('/[^a-zA-Z0-9]/', '_', $connectMessage->message));
            if (empty($normalized) || empty($message)) {
                $normalized = 'unknown-connect-error';
                $message = 'Unknown error';
            }
            $translation = $namespace->get(
                $normalized,
                $message,
                true
            );

            $connectMessage->message = $translation;
        }

        return $connectMessages;
    }

    /**
     * Event listener method for the checkout->finishAction. Will reserve products and redirect to
     * the confirm page if a product cannot be reserved
     *
     * @event Enlight_Controller_Action_PreDispatch_Frontend_Checkout
     *
     * @param \Enlight_Event_EventArgs $args
     */
    public function reserveConnectProductsOnCheckoutFinish(\Enlight_Event_EventArgs $args)
    {
        /** @var $controller \Enlight_Controller_Action */
        $controller = $args->getSubject();
        $request = $controller->Request();
        $view = $controller->View();
        $session = Shopware()->Session();
        $sdk = $this->getSDK();
        $helper = $this->getHelper();
        $userData = $session['sOrderVariables']['sUserData'];
        $paymentName = $userData['additional']['payment']['name'];

        if (($request->getActionName() != 'finish' && $request->getActionName() != 'payment')) {
            if (($request->getActionName() == 'confirm' && $paymentName == 'klarna_checkout')) {
                // BEP-1010 Fix for Klarna checkout
            } else {
                return;
            }
        }

        if (empty($session['sOrderVariables'])) {
            return;
        }

        if (!$this->getHelper()->hasBasketConnectProducts(Shopware()->SessionID())) {
            return;
        }

        $userData = $session['sOrderVariables']['sUserData'];
        $paymentId = $userData['additional']['payment']['id'];

        if ($this->isPaymentAllowed($paymentId) === false) {
            $connectMessage = new \stdClass();
            $connectMessage->message = 'frontend_checkout_cart_connect_payment_not_allowed';

            $connectMessages = [
                0 => [
                    'connectmessage' => $connectMessage,
                ],
            ];

            Shopware()->Session()->connectMessages = $this->translateConnectMessages($connectMessages);
            $controller->forward('confirm');
        }

        if (Shopware()->Config()->get('requirePhoneField')) {
            $this->enforcePhoneNumber($view);
        }

        $order = new \Shopware\Connect\Struct\Order();
        $order->orderItems = [];
        $order->deliveryAddress = $this->getDeliveryAddress($userData);

        $basket = $session['sOrderVariables']['sBasket'];

        /** @var \ShopwarePlugins\Connect\Components\Utils\OrderPaymentMapper $orderPaymentMapper */
        $orderPaymentMapper = new OrderPaymentMapper();
        $orderPaymentName = $userData['additional']['payment']['name'];
        $order->paymentType = $orderPaymentMapper->mapShopwareOrderPaymentToConnect($orderPaymentName);

        foreach ($basket['content'] as $row) {
            if (!empty($row['mode'])) {
                continue;
            }

            $articleDetailId = $row['additional_details']['articleDetailsID'];
            if ($helper->isRemoteArticleDetailDBAL($articleDetailId) === false) {
                continue;
            }
            $shopProductId = $helper->getShopProductId($articleDetailId);

            $products = $helper->getRemoteProducts([$shopProductId->sourceId], $shopProductId->shopId);
            $products = $this->getHelper()->prepareConnectUnit($products);

            if (empty($products)) {
                continue;
            }
            $product = $products[0];

            if ($product === null || $product->shopId === null) {
                continue;
            }

            $orderItem = new \Shopware\Connect\Struct\OrderItem();
            $orderItem->product = $product;
            $orderItem->count = (int) $row['quantity'];
            $order->orderItems[] = $orderItem;
        }

        if (empty($order->orderItems)) {
            return;
        }

        try {
            $order = $this->eventManager->filter(
                'Connect_Subscriber_OrderReservation_OrderFilter',
                $order
            );

            /** @var $reservation \Shopware\Connect\Struct\Reservation */
            $reservation = $sdk->reserveProducts($order);

            if (!$reservation || !$reservation->success) {
                throw new \Exception('Error during reservation');
            }

            if (!empty($reservation->messages)) {
                $messages = $reservation->messages;
            }
        } catch (\Exception $e) {
            $this->getLogger()->write(true, 'Error during reservation', $e, 'reservation');
            $messages = $this->getNotAvailableMessageForProducts(array_map(
                function ($orderItem) {
                    return $orderItem->product;
                },
                $order->orderItems
            ));
        }

        if (!empty($messages)) {
            Shopware()->Session()->connectMessages = $messages;
            $controller->forward('confirm');
        } else {
            Shopware()->Session()->connectReservation = serialize($reservation);
        }
    }

    /**
     * Helper method to create an address struct from shopware session info
     *
     * @param $userData
     *
     * @return \Shopware\Connect\Struct\Address
     */
    private function getDeliveryAddress($userData)
    {
        if (!$userData) {
            return $this->createDummyAddres('DEU');
        }
        $shippingData = $userData['shippingaddress'];
        $address = new \Shopware\Connect\Struct\Address();
        $address->zip = $shippingData['zipcode'];
        $address->city = $shippingData['city'];
        $address->country = $userData['additional']['countryShipping']['iso3']; //when the user is not logged in
        $address->phone = $userData['billingaddress']['phone'];
        $address->email = $userData['additional']['user']['email'];
        if (!empty($userData['additional']['stateShipping']['shortcode'])) {
            $address->state = $userData['additional']['stateShipping']['shortcode'];
        }
        $address->firstName = $shippingData['firstname'];
        $address->surName = $shippingData['lastname'];
        if (!empty($shippingData['company'])) {
            $address->company = $shippingData['company'];
        }
        $address->street = $shippingData['street'];
        $address->streetNumber = (string) $shippingData['streetnumber'];

        return $address;
    }

    private function createDummyAddres($country = 'DEU')
    {
        return new \Shopware\Connect\Struct\Address([
            'country' => $country,
            'firstName' => 'Shopware',
            'surName' => 'AG',
            'street' => 'Eggeroder Str. 6',
            'zip' => '48624',
            'city' => 'Schöppingen',
            'phone' => '+49 (0) 2555 92885-0',
            'email' => 'info@shopware.com',
        ]);
    }

    /**
     * Hooks the sSaveOrder frontend method and reserves the connect products
     *
     * @param $orderNumber
     *
     * @throws \ShopwarePlugins\Connect\Components\Exceptions\CheckoutException
     */
    public function checkoutReservedProducts($orderNumber)
    {
        $sdk = $this->getSDK();

        if (empty($orderNumber)) {
            return;
        }

        $reservation = unserialize(Shopware()->Session()->connectReservation);
        if ($reservation !== null && $reservation !== false) {
            $result = $sdk->checkout($reservation, $orderNumber);
            foreach ($result as $shopId => $success) {
                if (!$success) {
                    $e = new CheckoutException("Could not checkout from warehouse {$shopId}");
                    $this->getLogger()->write(true, 'Error during checkout with this reservation: ' . json_encode($reservation, JSON_PRETTY_PRINT), $e, 'checkout');
                    throw $e;
                }
            }
            $this->getHelper()->clearConnectReservation();
        }
    }

    /**
     * Asks the user to leave is phone number if connect products are in the basket and the
     * phone number was not configured, yet.
     *
     * @param \Enlight_View_Default $view
     */
    public function enforcePhoneNumber($view)
    {
        if (Shopware()->Session()->sUserId && $this->getHelper()->hasBasketConnectProducts(Shopware()->SessionID())) {
            $id = Shopware()->Session()->sUserId;

            $sql = 'SELECT phone FROM s_user_billingaddress WHERE userID = :id';
            $result = Shopware()->Db()->fetchOne($sql, ['id' => $id]);
            if (!$result) {
                $view->assign('phoneMissing', true);
            }
        }
    }

    protected function getNotAvailableMessageForProducts($products)
    {
        $messages = [];
        /* \Shopware\Connect\Struct\Product */
        foreach ($products as $product) {
            $messages[] = new Message([
                'message' => 'Due to technical reasons, product %product is not available.',
                'values' => [
                    'product' => $product->title,
                ],
            ]);
        }

        return $messages;
    }

    /**
     * @param \Shopware\Connect\Struct\CheckResult $checkResult
     * @param $connectMessages
     *
     * @return mixed
     */
    protected function getNotShippableMessages($checkResult, $connectMessages)
    {
        if (!$checkResult instanceof CheckResult) {
            return $connectMessages;
        }

        $namespace = Shopware()->Snippets()->getNamespace('frontend/checkout/connect');

        foreach ($checkResult->shippingCosts as $shipping) {
            if ($shipping->isShippable === false) {
                $connectMessages[] = new Message([
                    'message' => $namespace->get(
                            'frontend_checkout_cart_connect_not_shippable',
                            'Ihre Bestellung kann nicht geliefert werden',
                            true
                        ),
                ]);
            }
        }

        return $connectMessages;
    }

    /**
     * @param $orderNumber
     */
    private function setOrderStatusError($orderNumber)
    {
        $repo = $this->manager->getRepository('Shopware\Models\Order\Order');

        /** @var \Shopware\Models\Order\Order $order */
        $order = $repo->findOneBy(['number' => $orderNumber]);

        $repoStatus = $this->manager->getRepository(Status::class);
        $status = $repoStatus->findOneBy(['name' => ConnectOrderUtil::ORDER_STATUS_ERROR, 'group' => Status::GROUP_STATE]);

        $order->setOrderStatus($status);
        $this->manager->persist($order);
        $this->manager->flush();
    }

    /**
     * Check is allowed payment method with connect products
     *
     * @param int $paymentId
     *
     * @return bool
     */
    private function isPaymentAllowed($paymentId)
    {
        if ($paymentId < 1) {
            return false;
        }

        $paymentRepository = Shopware()->Models()->getRepository('Shopware\Models\Payment\Payment');
        /** @var \Shopware\Models\Payment\Payment $payment */
        $payment = $paymentRepository->find($paymentId);

        if (!$payment) {
            return false;
        }

        if ($payment->getAttribute()->getConnectIsAllowed() == 0) {
            return false;
        }

        return true;
    }
}
