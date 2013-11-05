<?php

namespace Shopware\Bepado\Subscribers;

class Checkout extends BaseSubscriber
{

    /** @var  \Shopware\Bepado\BasketHelper */
    protected $basketHelper;

    public function getSubscribedEvents()
    {
        return array(
            'Enlight_Controller_Action_PostDispatch_Frontend_Checkout' => 'fixBasketForBepado',
            'sOrder::sSaveOrder::after' => 'checkoutReservedProducts',
            'Enlight_Controller_Action_PreDispatch_Frontend_Checkout' => 'reserveBepadoProductsOnCheckoutFinish',
        );
    }

    public function __construct($basketHelper)
    {
        $this->basketHelper = $basketHelper;

        parent::__construct();
    }

    /**
     * @param mixed $basketHelper
     */
    public function setBasketHelper($basketHelper)
    {
        $this->basketHelper = $basketHelper;
    }

    /**
     * @return \Shopware\Bepado\BasketHelper
     */
    public function getBasketHelper()
    {
        return $this->basketHelper;
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

        if(!in_array($actionName, array('confirm', 'cart'))) {
            return;
        }
        if(empty($view->sBasket) || !$request->isDispatched()) {
            return;
        }
        if(!empty($view->sOrderNumber)) {
            return;
        }

        $this->registerMyTemplateDir();
        $view->extendsTemplate('frontend/bepado/checkout.tpl');

        $sdk = $this->getSDK();

        // Wrap the basket array in order to make it some more readable
        $basketHelper = $this->getBasketHelper();
        $basketHelper->setBasket($view->sBasket);


        // If no messages are shown, yet, check products from remote shop and build message array
        if(($bepadoMessages = $view->getAssign('bepadoMessages')) === null) {
            $bepadoMessages = array();
            foreach($basketHelper->getBepadoProducts() as $shopId => $products) {
                /** @var $response \Bepado\SDK\Struct\Message */
                $response = $sdk->checkProducts($products);
                if($response !== true) {
                    $bepadoMessages[$shopId] = $response;
                }
            }
        }

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

        /** @var $reservation \Bepado\SDK\Struct\Reservation */
        $reservation = $sdk->reserveProducts($order);
        if(!empty($reservation->messages)) {
            $view->assign('bepadoMessages', $reservation->messages);
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
        if(!empty($userData['additional']['stateShipping']['shortcode'])) {
            $address->state = $userData['additional']['stateShipping']['shortcode'];
        }
        $address->name = $shippingData['firstname'] . ' ' . $shippingData['lastname'];
        if(!empty($shippingData['company'])) {
            $address->company = $shippingData['company'];
        }
        $address->line1 = $shippingData['street'] . ' ' . $shippingData['streetnumber'];
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




}