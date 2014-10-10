<?php

namespace Shopware\Bepado\Components;

use Shopware\Bepado\Components\CategoryQuery\RelevanceSorter;
use Shopware\Bepado\Components\CategoryQuery\Sw41Query;
use Bepado\SDK;
use Shopware\Bepado\Components\OrderQuery\RemoteOrderQuery;
use Shopware\Bepado\Components\Payment\ProductPayments;
use Shopware\Bepado\Components\ProductQuery\LocalProductQuery;
use Shopware\Bepado\Components\ProductQuery\RemoteProductQuery;
use Shopware\Bepado\Components\Utils\CountryCodeResolver;

/**
 * Creates services like SDK, Helper and BasketHelper and injects the needed dependencies
 *
 * Class BepadoFactory
 * @package Shopware\Bepado\Components
 */
class BepadoFactory
{
    private $helper;
    private $sdk;

    private $modelManager;
    private $pluginVersion;

    /** @var  \Shopware\Bepado\Components\Config */
    private $configComponent;

    public function __construct($version='')
    {
        $this->pluginVersion = $version;
    }

    /**
     * @return SDK\SDK
     */
    public function getSDK()
    {
        if(!$this->sdk) {
            $this->sdk = Shopware()->Bootstrap()->getResource('BepadoSDK');
        }

        return $this->sdk;
    }

    /**
     * @return \Shopware\Components\Model\ModelManager
     */
    private function getModelManager()
    {
        if ($this->modelManager === null) {
            $this->modelManager = Shopware()->Models();
        }

        return $this->modelManager;
    }

    /**
     * Will create an instance of the \Bepado\Sdk\Sdk object.
     *
     * @return SDK\SDK
     */
    public function createSDK()
    {
        $connection = Shopware()->Db()->getConnection();
        $manager = $this->getModelManager();
        $front = Shopware()->Front();
        $helper = $this->getHelper();
        $apiKey = $this->getConfigComponent()->getConfig('apiKey');

        $gateway = new SDK\Gateway\PDO($connection);

        /*
         * The debugHost allows to specify an alternative bepado host.
         * Furthermore currently only one debugHost for *all* service can be specified
         */
        $debugHost = $this->getConfigComponent()->getConfig('bepadoDebugHost');
        if (!empty($debugHost)) {
            $debugHost = str_replace(array('http://', 'https://'),'', $debugHost);
             // Set the debugHost as environment vars for the DependencyResolver
            putenv("_SOCIALNETWORK_HOST=sn.{$debugHost}");
            putenv("_TRANSACTION_HOST=transaction.{$debugHost}");
            putenv("_SEARCH_HOST=search.{$debugHost}");
        }

        return new SDK\SDK(
            $apiKey,
            $this->getSdkRoute($front),
            $gateway,
            new ProductToShop(
                $helper,
                $manager,
                $this->getImageImport(),
                $this->getConfigComponent()
            ),
            new ProductFromShop(
                $helper,
                $manager
            ),
            new ShopwareErrorHandler(),
            null,
            $this->getPluginVersion(),
            new ProductPayments()
        );
    }

    /**
     * @return ImageImport
     */
    public function getImageImport()
    {
        return new ImageImport($this->getModelManager(), $this->getHelper());
    }

    /**
     * Returns a route to the bepado gateway controller
     *
     * @param $front \Enlight_Controller_Front
     * @return string
     */
    private function getSdkRoute($front)
    {
        if ( ! $front->Router()) {
            return '';
        }

        $url = $front->Router()->assemble(array(
            'module' => 'backend',
            'controller' => 'bepado_gateway',
            'fullPath' => true
        ));
        $hasSSL = $this->getConfigComponent()->getConfig('hasSSL', 0);
        if ($hasSSL) {
            $url = str_replace('http://', 'https://', $url);
        }

        return $url;
    }

    /**
     * @return Helper
     */
    public function getHelper()
    {
        if($this->helper === null) {
            $this->helper = new Helper(
                $this->getModelManager(),
                $this->getCategoryQuery(),
                $this->getProductQuery()
            );
        }

        return $this->helper;
    }

    /**
     * Will return a version string for this plugin. Will also append the SW version
     * in order to make the information more useful.
     */
    public function getPluginVersion()
    {
        $swVersion = \Shopware::VERSION;

        if ($swVersion == '___VERSION___') {
            $swVersion = 'DEV';
        }

        $version = sprintf('sw%s_%s', $swVersion, $this->pluginVersion);

        return $version;
    }

    public function getBasketHelper()
    {
        return new BasketHelper (
            Shopware()->Db(),
            $this->getSDK(),
            $this->getHelper(),
            $this->getConfigComponent()->getConfig('checkoutShopInfo', 0)
        );
    }

    /**
     * @return ProductQuery
     */
    private function getProductQuery()
    {
        return new ProductQuery(
            $this->getLocalProductQuery(),
            $this->getRemoteProductQuery()
        );
    }

    /**
     * Returns category query depending on the current shopware version
     *
     * @return Sw41Query
     */
    public function getCategoryQuery()
    {
        return $this->getShopware41CategoryQuery();
    }

    /**
     * Getter for the shopware >= 4.1 category query
     *
     * @return Sw41Query
     */
    public function getShopware41CategoryQuery()
    {
        return new Sw41Query($this->getModelManager(), new RelevanceSorter());
    }

    /**
     * Checks if the current shopware version matches a given requirement
     *
     * @param $requiredVersion
     * @return bool
     */
    public function checkMinimumVersion($requiredVersion)
    {
         $version = Shopware()->Config()->version;

        if ($version === '___VERSION___') {
            return true;
        }

        return version_compare($version, $requiredVersion, '>=');
    }

    /**
     * @return RemoteProductQuery
     */
    private function getRemoteProductQuery()
    {
        return new RemoteProductQuery(
            $this->getModelManager(),
            $this->getConfigComponent()->getConfig('alternateDescriptionField')
        );
    }

    /**
     * @return LocalProductQuery
     */
    private function getLocalProductQuery()
    {
        return new LocalProductQuery(
            $this->getModelManager(),
            $this->getConfigComponent()->getConfig('alternateDescriptionField'),
            $this->getProductBaseUrl(),
            $this->getConfigComponent()
        );
    }

    private function getProductBaseUrl()
    {
        if (!Shopware()->Front()->Router()) {
            return null;
        }

        $exportDomain = $this->getConfigComponent()->getConfig('exportDomain');
        if (!empty($exportDomain)) {
            return $exportDomain;
        }

        return Shopware()->Front()->Router()->assemble(array(
            'module' => 'frontend',
            'controller' => 'bepado_product_gateway',
            'action' => 'product',
            'id' => '',
            'fullPath' => true
        ));
    }

    /**
     * @return Config
     */
    public function getConfigComponent()
    {
        if (!$this->configComponent) {
            $this->configComponent = new Config($this->getModelManager());
        }

        return $this->configComponent;
    }

    public function getCountryCodeResolver()
    {
        $customer = null;
        if (Shopware()->Session()->sUserId) {
            $customer = Shopware()->Models()->find('Shopware\Models\Customer\Customer', Shopware()->Session()->sUserId);
        }

        return new CountryCodeResolver(Shopware()->Models(), $customer, Shopware()->Session()->sCountry);
    }
}
