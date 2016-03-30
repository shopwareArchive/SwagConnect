<?php

namespace ShopwarePlugins\Connect\Components;

use Shopware\Connect\Gateway\PDO;
use ShopwarePlugins\Connect\Components\CategoryQuery\RelevanceSorter;
use ShopwarePlugins\Connect\Components\CategoryQuery\Sw41Query;
use Shopware\Connect\SDK;
use ShopwarePlugins\Connect\Components\CategoryResolver\AutoCategoryResolver;
use ShopwarePlugins\Connect\Components\CategoryResolver\DefaultCategoryResolver;
use ShopwarePlugins\Connect\Components\Gateway\ProductTranslationsGateway\PdoProductTranslationsGateway;
use ShopwarePlugins\Connect\Components\Marketplace\MarketplaceGateway;
use ShopwarePlugins\Connect\Components\Marketplace\MarketplaceSettingsApplier;
use ShopwarePlugins\Connect\Components\OrderQuery\RemoteOrderQuery;
use ShopwarePlugins\Connect\Components\ProductQuery\LocalProductQuery;
use ShopwarePlugins\Connect\Components\ProductQuery\RemoteProductQuery;
use ShopwarePlugins\Connect\Components\Translations\ProductTranslator;
use ShopwarePlugins\Connect\Components\Utils\CountryCodeResolver;
use Shopware\Components\DependencyInjection\Container;

/**
 * Creates services like SDK, Helper and BasketHelper and injects the needed dependencies
 *
 * Class ConnectFactory
 * @package ShopwarePlugins\Connect\Components
 */
class ConnectFactory
{
    private $helper;
    private $sdk;

    private $modelManager;
    private $pluginVersion;

    /**
     * @var Container
     */
    private $container;

    /** @var  \ShopwarePlugins\Connect\Components\Config */
    private $configComponent;

    private $marketplaceGateway;

    private $productTranslationsGateway;

    private $marketplaceSettingsApplier;

    public function __construct($version='')
    {
        $this->pluginVersion = $version;
    }

    /**
     * @return \Shopware\Connect\SDK
     */
    public function getSDK()
    {
        if(!$this->sdk) {
            $this->sdk = Shopware()->Bootstrap()->getResource('ConnectSDK');
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
     * @return Container
     */
    private function getContainer()
    {
        if ($this->container === null) {
            $this->container = Shopware()->Container();
        }

        return $this->container;
    }

    /**
     * Will create an instance of the \Shopware\Connect\SDK object.
     *
     * @return \Shopware\Connect\SDK
     */
    public function createSDK()
    {
        $connection = Shopware()->Db()->getConnection();
        $manager = $this->getModelManager();
        $front = Shopware()->Front();
        $helper = $this->getHelper();
        $apiKey = $this->getConfigComponent()->getConfig('apiKey');

        $gateway = new \Shopware\Connect\Gateway\PDO($connection);

        /*
         * The debugHost allows to specify an alternative connect host.
         * Furthermore currently only one debugHost for *all* service can be specified
         */
        $debugHost = $this->getConfigComponent()->getConfig('connectDebugHost');
        if (!empty($debugHost)) {
            $debugHost = str_replace(array('http://', 'https://'),'', $debugHost);
             // Set the debugHost as environment vars for the DependencyResolver
            putenv("_SOCIALNETWORK_HOST={$debugHost}");
            putenv("_TRANSACTION_HOST=transaction.{$debugHost}");
        }

        $categoryResolver = $this->getConfigComponent()->getConfig('createCategoriesAutomatically', false) == true ?
            new AutoCategoryResolver(
                $manager,
                $manager->getRepository('Shopware\Models\Category\Category'),
                $manager->getRepository('Shopware\CustomModels\Connect\RemoteCategory')
            ) :
            new DefaultCategoryResolver(
                $manager,
                $manager->getRepository('Shopware\CustomModels\Connect\RemoteCategory'),
                $manager->getRepository('Shopware\CustomModels\Connect\ProductToRemoteCategory')
            );
        return new SDK(
            $apiKey,
            $this->getSdkRoute($front),
            $gateway,
            new ProductToShop(
                $helper,
                $manager,
                $this->getImageImport(),
                $this->getConfigComponent(),
                new VariantConfigurator($manager, $this->getProductTranslationsGateway()),
                $this->getMarketplaceGateway(),
                $this->getProductTranslationsGateway(),
                $categoryResolver
            ),
            new ProductFromShop(
                $helper,
                $manager,
                $gateway,
                new Logger(Shopware()->Db())
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
        return new ImageImport(
            $this->getModelManager(),
            $this->getHelper(),
            $this->getContainer()->get('thumbnail_manager'),
            new Logger(Shopware()->Db())
        );
    }

    /**
     * Returns a route to the connect gateway controller
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
            'controller' => 'connect_gateway',
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
        $db = Shopware()->Db();
        return new BasketHelper (
            $db,
            $this->getSDK(),
            $this->getHelper(),
            new PDO($db->getConnection()),
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
            $this->getConfigComponent(),
            $this->getMarketplaceGateway(),
            new ProductTranslator(
                $this->getConfigComponent(),
                new PdoProductTranslationsGateway(Shopware()->Db()),
                $this->getModelManager(),
                $this->getProductBaseUrl()
            )
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
            'controller' => 'connect_product_gateway',
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

    public function getMarketplaceGateway()
    {
        if (!$this->marketplaceGateway) {
            $this->marketplaceGateway = new MarketplaceGateway($this->getModelManager());
        }

        return $this->marketplaceGateway;
    }

    public function getCountryCodeResolver()
    {
        $customer = null;
        if (Shopware()->Session()->sUserId) {
            $customer = Shopware()->Models()->find('Shopware\Models\Customer\Customer', Shopware()->Session()->sUserId);
        }

        return new CountryCodeResolver(Shopware()->Models(), $customer, Shopware()->Session()->sCountry);
    }

    public function getProductTranslationsGateway()
    {
        if (!$this->productTranslationsGateway) {
            $this->productTranslationsGateway = new PdoProductTranslationsGateway(Shopware()->Db());
        }

        return $this->productTranslationsGateway;
    }

    public function getMarketplaceApplier()
    {
        if (!$this->marketplaceSettingsApplier) {
            $this->marketplaceSettingsApplier = new MarketplaceSettingsApplier(
                $this->getConfigComponent(),
                Shopware()->Models(),
                Shopware()->Db()
            );
        }

        return $this->marketplaceSettingsApplier;
    }
}
