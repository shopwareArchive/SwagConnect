<?php

namespace Shopware\Bepado\Subscribers;

class BasketWidget extends BaseSubscriber
{

    public function getSubscribedEvents()
    {
        return array(
            'sBasket::sGetBasket::after' => 'storeBasketResultToSession',
            'Enlight_Controller_Action_PostDispatch_Widgets_Checkout' => 'fixBasketWidgetForBepado',
            'Enlight_Controller_Action_PostDispatch_Frontend_Checkout' => 'fixBasketWidgetForBepado'
        );
    }


    /**
     * Fix the basket widget
     *
     * @param \Enlight_Event_EventArgs $args
     */
    public function fixBasketWidgetForBepado(\Enlight_Event_EventArgs $args)
    {
        /** @var $action \Enlight_Controller_Action */
        $action = $args->getSubject();
        $view = $action->View();
        $request = $action->Request();
        $actionName = $request->getActionName();

        if (!in_array($actionName, array('info', 'ajaxAmount', 'ajaxCart'))) {
            return;
        }

        // If the basket is empty or does not contain bepado products return
        $basket = Shopware()->Session()->bepadoGetBasket;
        if (empty($basket) || !$this->getHelper()->hasBasketBepadoProducts(Shopware()->SessionID())) {
            return;
        }

        $basketHelper = $this->getBasketHelper();
        $basketHelper->setBasket($basket);

        // Return if we don't have any bepado products
        $bepadoProducts = $basketHelper->getBepadoProducts();
        if (empty($bepadoProducts)) {
            return;
        }

        // Fix the basket for bepado
        $basketHelper->fixBasket();
        $basketHelper->recalculate();
        $vars = $basketHelper->getDefaultTemplateVariables();

        // Fix the basket widget template
        if ($actionName == 'ajaxCart') {
            $view->sBasket = $vars['sBasket'];

            $view->sShippingcosts = $view->sBasket['sShippingcosts'];
            $view->sShippingcostsDifference = $view->sBasket['sShippingcostsDifference'];
            $view->sAmount = $view->sBasket['sAmount'];
            $view->sAmountWithTax = $view->sBasket['sAmountWithTax'];
            $view->sAmountTax = $view->sBasket['sAmountTax'];
            $view->sAmountNet = $view->sBasket['AmountNetNumeric'];
        } else {
            // Assign the new amount / quantity
            $view->sBasketQuantity = count($vars['sBasket']['content']);
            $view->sBasketAmount = $vars['sBasket']['Amount'];
        }
    }

    /**
     * Hook for the sGetBasket method, which will store the most recent basket to the session
     *
     * @event sBasket::sGetBasket::after
     * @param \Enlight_Hook_HookArgs $args
     */
    public function storeBasketResultToSession(\Enlight_Hook_HookArgs $args)
    {
        $basket = $args->getReturn();
        Shopware()->Session()->bepadoGetBasket = $basket;

        $args->setReturn($basket);
    }

}