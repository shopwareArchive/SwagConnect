<?php

namespace Shopware\Bepado\Subscribers;

use Shopware\Bepado\Components\ShippingCostBridge;
use Shopware\Bepado\Components\ShippingCosts\ShippingCostRuleVisitor;
use Shopware\Bepado\Components\Translations\TranslationService;

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
        $shippingCostsPage = $this->getConfigComponent()->getConfig('shippingCostsPage', 6);

        if ($customPage != $shippingCostsPage) {
            return;
        }

        $this->registerMyTemplateDir();

        Shopware()->Template()->assign(
            array(
                'bepadoShipping' => $this->getShippingCosts(),
                'bepadoShopInfo' => $this->getConfigComponent()->getConfig('detailShopInfo')
            )
        );
        $result = Shopware()->Template()->fetch('frontend/bepado/shipping_costs.tpl');

        $controller->View()->sContent = $result . $controller->View()->sContent;
    }

    protected function getShopInfo($shopId)
    {
        $info = Shopware()->BepadoSDK()->getShop($shopId);

        return array(
            'id' => $info->id,
            'name' => $info->name
        );
    }

    /**
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
     * @return mixed
     */
    public function getAllShippingCosts()
    {
        return Shopware()->BepadoSDK()->getShippingCostRules();
    }
}