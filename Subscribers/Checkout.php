<?php

namespace Shopware\Bepado\Subscribers;
use Bepado\SDK\Struct\Message;
use Shopware\Bepado\Components\Exceptions\NoRemoteProductException;
use Shopware\Bepado\Components\Logger;

/**
 * Handles the whole checkout manipulation, which is required for the bepado checkout
 *
 * Class Checkout
 * @package Shopware\Bepado\Subscribers
 */
class Checkout extends BaseSubscriber
{

    public function getSubscribedEvents()
    {
        return array(
            'Enlight_Controller_Action_PostDispatch_Frontend_Checkout' => 'fixBasketForBepado',
            'sOrder::sSaveOrder::after' => 'checkoutReservedProducts',
            'Enlight_Controller_Action_PreDispatch_Frontend_Checkout' => 'reserveBepadoProductsOnCheckoutFinish'
        );
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

        if(!in_array($actionName, array('confirm', 'cart', 'finish'))) {
            return;
        }
        if(empty($view->sBasket) || !$request->isDispatched()) {
            return;
        }

        if(!empty($view->sOrderNumber)) {
            return;
        }

        if (!$this->getHelper()->hasBasketBepadoProducts(Shopware()->SessionID())) {
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
                /** @var $response Message */
                try {
                    $response = $sdk->checkProducts($products);
                } catch (\Exception $e) {
                    $logger = new Logger(Shopware()->Db());
                    $logger->write(true, "Could not connect to bepado shop {$shopId}", $e);
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
        $basketHelper->recalculate();

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
                $normalized = strtolower(preg_replace('/[^a-zA-Z0-9]/', '_', $bepadoMessage->message));
                $translation = $namespace->get(
                    $normalized,
                    $bepadoMessage->message,
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

        if($request->getActionName() != 'finish') {
            return;
        }
        if(empty($session['sOrderVariables'])) {
			return;
		}

        if (!$this->getHelper()->hasBasketBepadoProducts(Shopware()->SessionID())) {
            return;
        }

        $this->enforcePhoneNumber($view);

        $order = new \Bepado\SDK\Struct\Order();
        $order->products = array();
        $userData = $session['sOrderVariables']['sUserData'];
        $order->deliveryAddress = $this->getDeliveryAddress($userData);
        $basket = $session['sOrderVariables']['sBasket'];

        foreach ($basket['content'] as $row) {
            if(!empty($row['mode'])) {
                continue;
            }
            $product = $helper->getProductById($row['articleID']);
            if($product === null || $product->shopId === null) {
                continue;
            }

            $orderItem = new \Bepado\SDK\Struct\OrderItem();
            $orderItem->product = $product;
            $orderItem->count = (int)$row['quantity'];
            $order->products[] = $orderItem;
        }

        if (empty($order->products)) {
            return;
        }

        /** @var $reservation \Bepado\SDK\Struct\Reservation */
        $reservation = $sdk->reserveProducts($order);
        if(!empty($reservation->messages)) {
            Shopware()->Session()->BepadoMessages = $reservation->messages;
            $controller->forward('confirm');
        } else {
            Shopware()->Session()->BepadoReservation = $reservation;
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
        $address->streetNumber = $shippingData['streetnumber'];
        return $address;
    }


    /**
     * Hooks the sSaveOrder frontend method and reserves the bepado products
     *
     * @event sOrder::sSaveOrder::after
     * @param \Enlight_Hook_HookArgs $args
     */
    public function checkoutReservedProducts(\Enlight_Hook_HookArgs $args)
    {
        $orderNumber = $args->getReturn();
        $sdk = $this->getSDK();

        if (empty($orderNumber)) {
            return;
        }

        $reservation = Shopware()->Session()->BepadoReservation;
        if($reservation !== null) {
            $sdk->checkout($reservation, $orderNumber);
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

    public function getNotAvailableMessageForProducts($products)
    {
        $messages = array();
        /** \Bepado\SDK\Struct\Product */
        foreach ($products as $product) {
            new Message(array(
                'message' => 'Availablility of product %product changed to %availability',
                'values' => array(
                    'product' => $product->title,
                    'availability' => 0
                )
            ));
        }

        return $messages;

    }
}