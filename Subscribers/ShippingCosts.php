<?php

namespace ShopwarePlugins\Connect\Subscribers;

use ShopwarePlugins\Connect\Components\ShippingCostBridge;
use ShopwarePlugins\Connect\Components\ShippingCosts\ShippingCostRuleVisitor;
use ShopwarePlugins\Connect\Components\Translations\TranslationService;

/**
 * The ShippingCosts class will prepend an automatically generated shipping cost table to the shop's shipping
 * cost page.
 * The actual shipping cost page can be configured in the backend
 *
 * Class ShippingCosts
 * @package ShopwarePlugins\Connect\Subscribers
 */
class ShippingCosts extends BaseSubscriber
{
    /** @var  /ShopwarePlugins\Connect\Components\Config */
    protected $configComponent;

    public function getSubscribedEvents()
    {
        return array(
            'Enlight_Controller_Action_PostDispatchSecure_Frontend_Custom' => 'extendShippingCosts'
        );
    }

    /**
     * @return \ShopwarePlugins\Connect\Components\Config
     */
    public function getConfigComponent()
    {
        if (!$this->configComponent) {
            $this->configComponent = new \ShopwarePlugins\Connect\Components\Config(Shopware()->Models());
        }

        return $this->configComponent;
    }

    /** @var  \ShopwarePlugins\Connect\Components\ConnectFactory */
    protected $factory;

    /**
     * Returns the connect factory
     *
     * @return \ShopwarePlugins\Connect\Components\ConnectFactory
     */
    protected function getFactory()
    {
        if ($this->factory === null) {
            $this->factory = new \ShopwarePlugins\Connect\Components\ConnectFactory();
        }

        return $this->factory;
    }

    /**
     * Find the best possible country code for the current user
     *
     * @return string
     */
    protected function getCountryCode()
    {
        $countryCodeUtil = $this->getFactory()->getCountryCodeResolver();

        return $countryCodeUtil->getIso3CountryCode();
    }

    /**
     * @return ShippingCostRuleVisitor
     */
    public function getShippingCostVisitor()
    {
        $shippingCostPreprocessor = new ShippingCostRuleVisitor(
            new TranslationService(Shopware()->Db(),
            $this->getCountryCode())
        );

        return $shippingCostPreprocessor;
    }

    /**
     * If the shown page is the shipping cost page, add the connect shipping cost information
     *
     * @param \Enlight_Event_EventArgs $args
     */
    public function extendShippingCosts(\Enlight_Event_EventArgs $args)
    {
        $controller = $args->getSubject();
        $request = $controller->Request();

        $customPage = $request->getParam('sCustom', null);
        $shippingCostsPage = $this->getConfigComponent()->getConfig('shippingCostsPage', 6, Shopware()->Shop()->getId());

        if ($customPage != $shippingCostsPage) {
            return;
        }

        $this->registerMyTemplateDir();

        Shopware()->Template()->assign(
            array(
                'connectShipping' => $this->getShippingCosts(),
                'connectShopInfo' => $this->getConfigComponent()->getConfig('detailShopInfo')
            )
        );
        $result = Shopware()->Template()->fetch('frontend/connect/shipping_costs.tpl');

        $controller->View()->sContent = $result . $controller->View()->sContent;
    }

    /**
     * Get name and ID for a given shopId
     *
     * @param $shopId
     * @return array
     */
    protected function getShopInfo($shopId)
    {
        $info = Shopware()->ConnectSDK()->getShop($shopId);

        return array(
            'id' => $info->id,
            'name' => $info->name
        );
    }

    /**
     * Get shipping costs and sort them by shopId and type
     *
     * @return array
     */
    public function getShippingCosts()
    {
        $shippingCosts = array();
        foreach ($this->getAllShippingCosts() as $shopId => $rules) {
            if ($this->hasImportedProductsFromShop($shopId)) {
                $shippingCostVisitor = $this->getShippingCostVisitor();
                $shippingCostVisitor->visit($rules);
                $shippingCosts[$shopId]['rules'] = $shippingCostVisitor->rules;
                $shippingCosts[$shopId]['shopInfo'] = $this->getShopInfo($shopId);
            }
        }

        return $shippingCosts;
    }

    /**
     * Get all shipping cost rules from the SDK
     *
     * @return mixed
     */
    public function getAllShippingCosts()
    {
        return Shopware()->ConnectSDK()->getShippingCostRules();
    }

    /**
     * Check for imported products from external shop
     * to the local shop
     *
     * @param $shopId
     * @return bool
     */
    private function hasImportedProductsFromShop($shopId)
    {
        $sql = 'SELECT COUNT(id) FROM s_plugin_connect_items WHERE shop_id = ?';

        return Shopware()->Db()->fetchOne($sql, array($shopId)) > 0;
    }
}