<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Subscribers;

/**
 * The basket widget shows the current basket amount and the current basket's products.
 * It needs to be modified in order to show the connect products / valuess
 *
 * Class BasketWidget
 * @package ShopwarePlugins\Connect\Subscribers
 */
class BasketWidget extends BaseSubscriber
{
    public function getSubscribedEvents()
    {
        return [
            'sBasket::sGetBasket::after' => 'storeBasketResultToSession',
            'Enlight_Controller_Action_PostDispatch_Widgets_Checkout' => 'fixBasketWidgetForConnect',
            'Enlight_Controller_Action_PostDispatch_Frontend_Checkout' => 'fixBasketWidgetForConnect'
        ];
    }

    /** @var \ShopwarePlugins\Connect\Components\ConnectFactory */
    protected $factory;

    protected function getFactory()
    {
        if ($this->factory === null) {
            $this->factory = new \ShopwarePlugins\Connect\Components\ConnectFactory();
        }

        return $this->factory;
    }

    protected function getCountryCode()
    {
        $countryCodeUtil = $this->getFactory()->getCountryCodeResolver();

        return $countryCodeUtil->getIso3CountryCode();
    }

    /**
     * Fix the basket widget
     *
     * @param \Enlight_Event_EventArgs $args
     */
    public function fixBasketWidgetForConnect(\Enlight_Event_EventArgs $args)
    {
        /** @var $action \Enlight_Controller_Action */
        $action = $args->getSubject();
        $view = $action->View();
        $request = $action->Request();
        $actionName = $request->getActionName();

        // ajaxCart was removed from array, because when user puts
        // connect article it's displayed in ajax cart, but
        // then puts local article and connect is missing
        if (!in_array($actionName, ['info', 'ajaxAmount'])) {
            return;
        }

        // If the basket is empty or does not contain connect products return
        $basket = Shopware()->Session()->connectGetBasket;
        if (empty($basket) || !$this->getHelper()->hasBasketConnectProducts(Shopware()->SessionID())) {
            return;
        }

        $basketHelper = $this->getBasketHelper();
        $basketHelper->setBasket($basket);

        // Return if we don't have any connect products
        $connectProducts = $basketHelper->getConnectProducts();
        if (empty($connectProducts)) {
            return;
        }

        // Fix the basket for connect
        $basketHelper->fixBasket();
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
            $view->sBasketQuantity = $vars['sBasket']['Quantity'];
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
        Shopware()->Session()->connectGetBasket = $basket;

        $args->setReturn($basket);
    }
}
