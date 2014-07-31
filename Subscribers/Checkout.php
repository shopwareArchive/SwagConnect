<?php

namespace Shopware\Bepado\Subscribers;
use Bepado\SDK\Struct\Message;
use Bepado\SDK\Struct\Order;
use Bepado\SDK\Struct\OrderItem;
use Bepado\SDK\Struct\Reservation;
use Bepado\SDK\Struct\TotalShippingCosts;
use Shopware\Bepado\Components\Exceptions\CheckoutException;
use Shopware\Bepado\Components\Logger;
use Shopware\Bepado\Components\Utils\CountryCodeResolver;
use Shopware\Bepado\Components\Utils\OrderPaymentMapper;
use Shopware\Plugin\Debug\Components\Utils;

/**
 * Handles the whole checkout manipulation, which is required for the bepado checkout
 *
 * Class Checkout
 * @package Shopware\Bepado\Subscribers
 */
class Checkout extends BaseSubscriber
{

    protected $logger;

    /** @var  string */
    private $newSessionId;

    /** @var  \Shopware\Bepado\Components\BepadoFactory */
    protected $factory;

    public function getSubscribedEvents()
    {
        return array(
            'Enlight_Controller_Action_PostDispatch_Frontend_Checkout' => 'fixBasketForBepado',
            'Shopware_Modules_Order_SaveOrder_FilterSQL' => 'checkoutReservedProducts',
            'Enlight_Controller_Action_PreDispatch_Frontend_Checkout' => 'reserveBepadoProductsOnCheckoutFinish',
            'Shopware_Modules_Admin_Regenerate_Session_Id' => 'updateSessionId',
        );
    }

    public function updateSessionId(\Enlight_Event_EventArgs $args)
    {
        $this->newSessionId = $args->get('newSessionId');
    }


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
            $this->factory = new \Shopware\Bepado\Components\BepadoFactory();
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
     * @return void
     */
    public function fixBasketForBepado(\Enlight_Event_EventArgs $args)
    {
        /** @var $action \Enlight_Controller_Action */
        $action = $args->getSubject();
        $view = $action->View();
        $request = $action->Request();
        $actionName = $request->getActionName();

        $sessionId = Shopware()->SessionID();

        $userId = Shopware()->Session()->sUserId;
        $hasBepadoProduct = $this->getHelper()->hasBasketBepadoProducts($sessionId, $userId);

        if ($hasBepadoProduct === false && $this->newSessionId) {
            $hasBepadoProduct = $this->getHelper()->hasBasketBepadoProducts($this->newSessionId);
        }

        $view->hasBepadoProduct = $hasBepadoProduct;

        if ($actionName == 'ajax_add_article') {
            $this->registerMyTemplateDir();
            $view->extendsTemplate('frontend/bepado/ajax_add_article.tpl');
        }

        if(!in_array($actionName, array('confirm', 'cart', 'finish'))) {
            return;
        }
        if(empty($view->sBasket) || !$request->isDispatched()) {
            return;
        }

        if(!empty($view->sOrderNumber)) {
            return;
        }

        if (!$hasBepadoProduct) {
            return;
        }

        $this->enforcePhoneNumber($view);

        $this->registerMyTemplateDir();
        $view->extendsTemplate('frontend/bepado/checkout.tpl');

        $sdk = $this->getSDK();

        // Wrap the basket array in order to make it some more readable
        $basketHelper = $this->getBasketHelper();
        $basketHelper->setBasket($view->sBasket);

        // If no messages are shown, yet, check products from remote shop and build message array
        if(($bepadoMessages = Shopware()->Session()->BepadoMessages) === null) {
            $bepadoMessages = array();

            foreach($basketHelper->getBepadoProducts() as $shopId => $products) {
                $products = $this->getHelper()->prepareBepadoUnit($products);
                /** @var $response Message */
                try {
                    $response = $sdk->checkProducts($products);
                } catch (\Exception $e) {
                    $this->getLogger()->write(true, 'Error during checkout', $e, 'checkout');
                    // If the checkout results in an exception because the remote shop is not available
                    // don't show the exception to the user but tell him to remove the products from that shop
                    $response = $this->getNotAvailableMessageForProducts($products);
                }
                if($response !== true) {
                    $bepadoMessages[$shopId] = $response;
                }
            }
        }

        if ($bepadoMessages) {
            $bepadoMessages = $this->translateBepadoMessages($bepadoMessages);
        }

        Shopware()->Session()->BepadoMessages = null;

        // If no products are bought from the local shop, move the first bepado shop into
        // the content section. Also set that shop's id in the template
        $shopId = $basketHelper->fixBasket();
        if ($shopId) {
            $view->shopId = $shopId;
        }

        // Increase amount and shipping costs by the amount of bepado shipping costs
        $basketHelper->recalculate($this->getCountryCode());

        $bepadoMessages = $this->getNotShippableMessages($basketHelper->getTotalShippingCosts(), $bepadoMessages);

        $view->assign($basketHelper->getDefaultTemplateVariables());

        // Set the sOrderVariables for the session based on the original content subarray of the basket array
        // @HL - docs?
        if($actionName == 'confirm') {
            $session = Shopware()->Session();
            /** @var $variables \ArrayObject */
            $variables = $session->offsetGet('sOrderVariables');

            $session->offsetSet('sOrderVariables', $basketHelper->getOrderVariablesForSession($variables));
        }

        $view->assign($basketHelper->getBepadoTemplateVariables($bepadoMessages));
    }

    /**
     * Helper to translate bepado messages from the SDK. Will use the normalized message itself as namespace key
     *
     * @param $bepadoMessages
     * @return mixed
     */
    private function translateBepadoMessages($bepadoMessages)
    {
        $namespace = Shopware()->Snippets()->getNamespace('frontend/checkout/bepado');

        foreach($bepadoMessages as $shopId => &$shopMessages) {
            foreach ($shopMessages as &$bepadoMessage) {
                $message = trim($bepadoMessage->message);
                $normalized = strtolower(preg_replace('/[^a-zA-Z0-9]/', '_', $bepadoMessage->message));
                if (empty($normalized) || empty($message)) {
                    $normalized = "unknown-bepado-error-{$shopId}";
                    $message = "Unknown error for {$shopId}";
                }
                $translation = $namespace->get(
                    $normalized,
                    $message,
                    true
                );

                $bepadoMessage->message = $translation;
            }

        }

        return $bepadoMessages;
    }

    /**
     * Event listener method for the checkout->finishAction. Will reserve products and redirect to
     * the confirm page if a product cannot be reserved
     *
     * @event Enlight_Controller_Action_PreDispatch_Frontend_Checkout
     * @param \Enlight_Event_EventArgs $args
     */
    public function reserveBepadoProductsOnCheckoutFinish(\Enlight_Event_EventArgs $args)
    {
        /** @var $controller \Enlight_Controller_Action */
        $controller = $args->getSubject();
        $request = $controller->Request();
        $view = $controller->View();
        $session = Shopware()->Session();
        $sdk = $this->getSDK();
        $helper = $this->getHelper();

        if($request->getActionName() != 'finish' && $request->getActionName() != 'payment') {
            return;
        }

        if(empty($session['sOrderVariables'])) {
			return;
		}

        if (!$this->getHelper()->hasBasketBepadoProducts(Shopware()->SessionID())) {
            // reset bepado reserved products in session
            Shopware()->Session()->BepadoReservation = null;
            return;
        }

        $userData = $session['sOrderVariables']['sUserData'];
        $paymentId = $userData['additional']['payment']['id'];
        if ($this->isPaymentAllowed($paymentId) === false) {
            $bepadoMessage = new \stdClass();
            $bepadoMessage->message = 'frontend_checkout_cart_bepado_payment_not_allowed';

            $bepadoMessages = array(
                0 => array(
                    'bepadomessage' => $bepadoMessage
                )
            );

            Shopware()->Session()->BepadoMessages = $this->translateBepadoMessages($bepadoMessages);
            $controller->forward('confirm');
        }

        $this->enforcePhoneNumber($view);

        $order = new \Bepado\SDK\Struct\Order();
        $order->orderItems = array();
        $order->deliveryAddress = $this->getDeliveryAddress($userData);

        $basket = $session['sOrderVariables']['sBasket'];

        /** @var \Shopware\Bepado\Components\Utils\OrderPaymentMapper $orderPaymentMapper */
        $orderPaymentMapper = new OrderPaymentMapper();
        $orderPaymentName = $userData['additional']['payment']['name'];
        $order->paymentType = $orderPaymentMapper->mapShopwareOrderPaymentToBepado($orderPaymentName);

        foreach ($basket['content'] as $row) {
            if(!empty($row['mode'])) {
                continue;
            }

            $products = $helper->getRemoteProducts(array($row['articleID']));
            $products = $this->getHelper()->prepareBepadoUnit($products);

            if (empty($products)) {
                continue;
            } else {
                $product = $products[0];
            }

            if($product === null || $product->shopId === null) {
                continue;
            }

            $orderItem = new \Bepado\SDK\Struct\OrderItem();
            $orderItem->product = $product;
            $orderItem->count = (int)$row['quantity'];
            $order->orderItems[] = $orderItem;
        }

        if (empty($order->orderItems)) {
            return;
        }

        try {
            /** @var $reservation \Bepado\SDK\Struct\Reservation */
            $reservation = $sdk->reserveProducts($order);
            if(!empty($reservation->messages)) {
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

        if(!empty($messages)) {
            Shopware()->Session()->BepadoMessages = $messages;
            $controller->forward('confirm');
        } else {
            Shopware()->Session()->BepadoReservation = serialize($reservation);
        }
    }

    /**
     * Helper method to create an address struct from shopware session info
     *
     * @param $userData
     * @return \Bepado\SDK\Struct\Address
     */
    private function getDeliveryAddress($userData)
    {
        $shippingData = $userData['shippingaddress'];
        $address = new \Bepado\SDK\Struct\Address();
        $address->zip = $shippingData['zipcode'];
        $address->city = $shippingData['city'];
        $address->country = $userData['additional']['countryShipping']['iso3'];
        $address->phone = $userData['billingaddress']['phone'];
        $address->email = $userData['additional']['user']['email'];
        if(!empty($userData['additional']['stateShipping']['shortcode'])) {
            $address->state = $userData['additional']['stateShipping']['shortcode'];
        }
        $address->firstName = $shippingData['firstname'];
        $address->surName = $shippingData['lastname'];
        if(!empty($shippingData['company'])) {
            $address->company = $shippingData['company'];
        }
        $address->street = $shippingData['street'];
        $address->streetNumber = (string) $shippingData['streetnumber'];
        return $address;
    }


    /**
     * Hooks the sSaveOrder frontend method and reserves the bepado products
     *
     * @event Shopware_Modules_Order_SaveOrder_FilterSQL
     * @throws CheckoutException
     * @param \Enlight_Event_EventArgs $args
     */
    public function checkoutReservedProducts(\Enlight_Event_EventArgs $args)
    {
        $orderNumber = $args->getSubject()->sOrderNumber;

        $sdk = $this->getSDK();

        if (empty($orderNumber)) {
            return;
        }

        $reservation = unserialize(Shopware()->Session()->BepadoReservation);
        if($reservation !== null && $reservation !== false) {
            $result = $sdk->checkout($reservation, $orderNumber);
            foreach($result as $shopId => $success) {
                if (!$success) {
                    $e = new CheckoutException("Could not checkout from warehouse {$shopId}");
                    $this->getLogger()->write(true, 'Error during checkout with this reservation: ' . json_encode($reservation, JSON_PRETTY_PRINT), $e, 'checkout');
                    throw $e;
                }
            }
        }
    }

    /**
     * Asks the user to leave is phone number if bepado products are in the basket and the
     * phone number was not configured, yet.
     *
     * @param $view
     * @return null
     */
    public function enforcePhoneNumber($view)
    {
        if (Shopware()->Session()->sUserId && $this->getHelper()->hasBasketBepadoProducts(Shopware()->SessionID())) {
            $id = Shopware()->Session()->sUserId;

            $sql = 'SELECT phone FROM s_user_billingaddress WHERE userID = :id';
            $result = Shopware()->Db()->fetchOne($sql, array('id' => $id));
            if (!$result) {
                $view->assign('phoneMissing', true);
            }
        }
    }

    protected function getNotAvailableMessageForProducts($products)
    {
        $messages = array();
        /** \Bepado\SDK\Struct\Product */
        foreach ($products as $product) {
            $messages[] = new Message(array(
                'message' => 'Availability of product %product changed to %availability',
                'values' => array(
                    'product' => $product->title,
                    'availability' => 0
                )
            ));
        }

        return $messages;
    }

    /**
     * @param \Bepado\SDK\Struct\TotalShippingCosts $totalShippingCosts
     * @param array $bepadoMessages
     * @return array
     */
    protected function getNotShippableMessages(TotalShippingCosts $totalShippingCosts, $bepadoMessages)
    {
        $namespace = Shopware()->Snippets()->getNamespace('frontend/checkout/bepado');

        foreach ($totalShippingCosts->shops as $shop) {
            if ($shop->isShippable === false) {
                $bepadoMessages[$shop->shopId][] = new Message(array(
                    'message' => $namespace->get(
                            'frontend_checkout_cart_bepado_not_shippable',
                            'Ihre Bestellung kann nicht geliefert werden',
                            true
                        )
                ));
            }
        }

        return $bepadoMessages;
    }

    /**
     * Check is allowed payment method with bepado products
     * @param int $paymentId
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

        if ($payment->getAttribute()->getBepadoIsAllowed() == 0) {
            return false;
        }

        return true;
    }
}