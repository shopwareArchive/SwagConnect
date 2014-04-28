<?php

namespace Shopware\Bepado\Subscribers;

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

        $controller->View()->extendsTemplate('frontend/bepado/header_extension.tpl');

        Shopware()->Template()->assign(
            array(
                'bepadoShipping' => $this->getShippingCosts(),
                'bepadoShopInfo' => $this->getConfigComponent()->getConfig('detailShopInfo')
            )
        );
        $result = Shopware()->Template()->fetch('frontend/bepado/shipping_costs.tpl');

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
            $shippingCostVisitor = $this->getShippingCostVisitor();
            $shippingCostVisitor->visit($rules);
            $shippingCosts[$shopId]['rules'] = $shippingCostVisitor->rules;
            $shippingCosts[$shopId]['shopInfo'] = $this->getShopInfo($shopId);
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
}