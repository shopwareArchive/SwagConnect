<?php

namespace Shopware\Bepado\Subscribers;

use Bepado\SDK\ShippingCosts\Rule\CountryDecorator;
use Bepado\SDK\ShippingCosts\Rule\FixedPrice;
use Bepado\SDK\ShippingCosts\Rule\Product;
use Bepado\SDK\ShippingCosts\Rules;
use Shopware\Bepado\Components\ShippingCostBridge;
use Shopware\Bepado\Components\ShippingCosts\ShippingCostRuleVisitor;
use Shopware\Bepado\Components\Translations\TranslationService;

/**
 * The ShippingCosts class will prepend an automatically generated shipping cost table to the shop's shipping
 * cost page.
 * The actual shipping cost page can be configured in the backend
 *
 * Class ShippingCosts
 * @package Shopware\Bepado\Subscribers
 */
class ShippingCosts extends BaseSubscriber
{
    /** @var  /Shopware\Bepado\Components\Config */
    protected $configComponent;

    public function getSubscribedEvents()
    {
        return array(
            'Enlight_Controller_Action_PostDispatchSecure_Frontend_Custom' => 'extendShippingCosts'
        );
    }

    /**
     * @return \Shopware\Bepado\Components\Config
     */
    public function getConfigComponent()
    {
        if (!$this->configComponent) {
            $this->configComponent = new \Shopware\Bepado\Components\Config(Shopware()->Models());
        }

        return $this->configComponent;
    }

    /** @var  \Shopware\Bepado\Components\BepadoFactory */
    protected $factory;

    /**
     * Returns the bepado factory
     *
     * @return \Shopware\Bepado\Components\BepadoFactory
     */
    protected function getFactory()
    {
        if ($this->factory === null) {
            $this->factory = new \Shopware\Bepado\Components\BepadoFactory();
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
     * If the shown page is the shipping cost page, add the bepado shipping cost information
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
        $sArticleId = $request->getParam('sArticle', null);

        $shippingCosts = array();
        if ($sArticleId > 0) {
            $products = $this->getHelper()->getRemoteProducts(array($sArticleId));
            $rules = $this->getSDK()->getProductShippingCostRules($products[0]);
            $rules = $this->prepareRules($rules);

            $shippingCostVisitor = $this->getShippingCostVisitor();
            $shippingCostVisitor->visit($rules);
            $shippingCosts[$products[0]->shopId]['rules'] = $shippingCostVisitor->rules;
            $shippingCosts[$products[0]->shopId]['shopInfo'] = $this->getShopInfo($products[0]->shopId);
        }
        $shippingCosts = array_merge($shippingCosts, $this->getShippingCosts());

        Shopware()->Template()->assign(
            array(
                'bepadoShipping' => $shippingCosts,
                'bepadoShopInfo' => $this->getConfigComponent()->getConfig('detailShopInfo')
            )
        );
        $result = Shopware()->Template()->fetch('frontend/bepado/shipping_costs.tpl');

        $controller->View()->sContent = $result . $controller->View()->sContent;
    }


    /**
     * Create necessary objects to create product based
     * shipping cost rules
     *
     * @param $productRules
     * @return Rules
     */
    protected function prepareRules($productRules)
    {
        $rules = array();
        foreach ($productRules->rules as $rule) {
            $fixedPrice = new FixedPrice();
            $fixedPrice->label = $rule->service;
            $fixedPrice->price = $rule->price;
            $fixedPrice->deliveryWorkDays = $rule->deliveryWorkDays;

            $countryDecorator = new CountryDecorator();
            $countryDecorator->countries = array($rule->country);
            $countryDecorator->delegatee = $fixedPrice;

            $productRule = new Product();
            $productRule->delegatee = $countryDecorator;

            $rules[] = $productRule;
        }
        $ab = new Rules();
        $ab->rules = $rules;

        return $ab;
    }

    /**
     * Get name and ID for a given shopId
     *
     * @param $shopId
     * @return array
     */
    protected function getShopInfo($shopId)
    {
        $info = Shopware()->BepadoSDK()->getShop($shopId);

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
        return Shopware()->BepadoSDK()->getShippingCostRules();
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
        $sql = 'SELECT COUNT(id) FROM s_plugin_bepado_items WHERE shop_id = ?';

        return Shopware()->Db()->fetchOne($sql, array($shopId)) > 0;
    }
}