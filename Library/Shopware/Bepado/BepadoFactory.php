<?php

namespace Shopware\Bepado;

use Shopware\Bepado\CategoryQuery\Sw41Query;
use Shopware\Bepado\CategoryQuery\Sw40Query;
use Bepado\SDK;

class BepadoFactory
{
    const CURRENT_SW_STABLE = '4.1';

    private $helper;
    private $sdk;

    private $modelManager;

    /**
     * @return SDK\SDK
     */
    public function getSDK()
    {
        if($this->sdk === null) {
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
        $requestSigner = null;

        /*
         * The debugHost allows to specify an alternative bepado host.
         * This will automatically make bepado use the noSecurityRequestSigner
         * and should never used in a productive environment.
         * Furthermore currently only one debugHost for *all* service can be specified
         */
        $debugHost = Shopware()->Config()->get('bepadoDebugHost');
        if (!empty($debugHost)) {
            $debugHost = ltrim($debugHost, 'http://');
            // Set the debugHost as environment vars for the DependencyResolver
            putenv("_SOCIALNETWORK_HOST={$debugHost}");
            putenv("_TRANSACTION_HOST={$debugHost}");
            putenv("_SEARCH_HOST={$debugHost}");
            $requestSigner = $this->getNoSecurityRequestSigner($gateway, $apiKey);
        }

        return new SDK\SDK(
            $apiKey,
            $this->getSdkRoute($front),
            $gateway,
            new ProductToShop(
                $helper,
                $manager
            ),
            new ProductFromShop(
                $helper,
                $manager
            ),
            null,
            $requestSigner
        );
    }

    /**
     * Creates an instance of the NoSecurityRequestSigner
     *
     * @param $gateway
     * @param $apiKey
     * @return SDK\HttpClient\NoSecurityRequestSigner
     */
    private function getNoSecurityRequestSigner($gateway, $apiKey)
    {
        return new SDK\HttpClient\NoSecurityRequestSigner(
            $gateway,
            new SDK\Service\Clock(),
            $apiKey
        );
    }

    /**
     * Returns a route to the bepado gateway controller
     *
     * @param $front
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
     * @return \Shopware\Bepado\Helper
     */
    public function getHelper()
    {
        if($this->helper === null) {
            $this->helper = new \Shopware\Bepado\Helper(
                $this->getModelManager(),
                $this->getImagePath(),
                Shopware()->Config()->get('productDescriptionField'),
                $this->getCategoryQuery(),
                $this->getSDK()
            );
        }

        return $this->helper;
    }

    /**
     * Returns URL for the shopware image directory
     *
     * @return string
     */
    protected function getImagePath()
    {
        $request = Shopware()->Front()->Request();

        if (!$request) {
            return '';
        }

        $imagePath = $request->getScheme() . '://'
                   . $request->getHttpHost() . $request->getBasePath();
        $imagePath .= '/media/image/';

        return $imagePath;
    }

    /**
     * Returns category query depending on the current shopware version
     *
     * @return Sw40Query|Sw41Query
     */
    public function getCategoryQuery()
    {
        return $this->isMinorVersion('4.1')
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
    public function isMinorVersion($requiredVersion)
    {
         $version = Shopware()->Config()->version;

         if ($version === '___VERSION___' && $requiredVersion === self::CURRENT_SW_STABLE) {
             return true;
         }

         return version_compare($version, $requiredVersion . '.0', '>=') &&
                version_compare($version, $requiredVersion . '.99', '<');
    }
}
