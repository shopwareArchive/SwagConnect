<?php

namespace Shopware\Bepado\Components;

use Shopware\Bepado\Components\CategoryQuery\Sw41Query;
use Shopware\Bepado\Components\CategoryQuery\Sw40Query;
use Bepado\SDK;
use Shopware\Bepado\Components\ProductQuery\LocalProductQuery;
use Shopware\Bepado\Components\ProductQuery\RemoteProductQuery;

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
        $apiKey = Shopware()->Config()->get('apiKey');

        $gateway = new SDK\Gateway\PDO($connection);

        /*
         * The debugHost allows to specify an alternative bepado host.
         * Furthermore currently only one debugHost for *all* service can be specified
         */
        $debugHost = Shopware()->Config()->get('bepadoDebugHost');
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
                Shopware()->Config()
            ),
            new ProductFromShop(
                $helper,
                $manager
            ),
            new ShopwareErrorHandler(),
            null,
            $this->getPluginVersion()
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

        return $front->Router()->assemble(array(
            'module' => 'backend',
            'controller' => 'bepado_gateway',
            'fullPath' => true
        ));
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
                $this->getProductQuery(),
                Shopware()->Front()->Router()
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
            Shopware()->Config()->getByNamespace('SwagBepado', 'checkoutShopInfo')
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
     * @return Sw40Query|Sw41Query
     */
    public function getCategoryQuery()
    {
        return $this->checkMinimumVersion('4.1.0')
            ? $this->getShopware41CategoryQuery()
            : $this->getShopware40CategoryQuery();
    }

    /**
     * Getter for the shopware < 4.1 category query
     *
     * @return Sw40Query
     */
    public function getShopware40CategoryQuery()
    {
        return new Sw40Query($this->getModelManager());
    }

    /**
     * Getter for the shopware >= 4.1 category query
     *
     * @return Sw41Query
     */
    public function getShopware41CategoryQuery()
    {
        return new Sw41Query($this->getModelManager());
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
        return new RemoteProductQuery($this->getModelManager(), Shopware()->Config()->get('alternateDescriptionField'));
    }

    /**
     * @return LocalProductQuery
     */
    private function getLocalProductQuery()
    {
        return new LocalProductQuery($this->getModelManager(), Shopware()->Config()->get('alternateDescriptionField'));
    }
}
