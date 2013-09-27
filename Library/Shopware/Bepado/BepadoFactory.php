<?php

namespace Shopware\Bepado;

use Shopware\Bepado\CategoryQuery\Sw41Query;
use Shopware\Bepado\CategoryQuery\Sw40Query;

class BepadoFactory
{
    const CURRENT_SW_STABLE = '4.1';

    private $helper;
    private $sdk;

    private $modelManager;

    /**
     * @return Bepado\SDK\SDK
     */
    public function getSDK()
    {
        if($this->sdk === null) {
            $this->sdk = $this->Application()->Bootstrap()->getResource('BepadoSDK');
        }

        return $this->sdk;
    }

    private function getModelManager()
    {
        if ($this->modelManager === null) {
            $this->modelManager = Shopware()->Models();
        }

        return $this->modelManager;
    }

    public function createSDK()
    {
        $connection = Shopware()->Db()->getConnection();
        $manager = $this->getModelManager();
        $front = Shopware()->Front();
        $helper = $this->getHelper();
        $apiKey = Shopware()->Config()->get('apiKey');

        return new \Bepado\SDK\SDK(
            $apiKey,
            $this->getSdkRoute($front),
            new \Bepado\SDK\Gateway\PDO($connection),
            new \Shopware\Bepado\ProductToShop(
                $helper,
                $manager
            ),
            new \Shopware\Bepado\ProductFromShop(
                $helper,
                $manager
            )
        );
    }

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
                $this->getCategoryQuery()
            );
        }

        return $this->helper;
    }

    /**
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

    public function getCategoryQuery()
    {
        return $this->isMinorVersion('4.1')
            ? $this->getShopware41CategoryQuery()
            : $this->getShopware40CategoryQuery();
    }

    public function getShopware40CategoryQuery()
    {
        return new Sw40Query($this->getModelManager());
    }

    public function getShopware41CategoryQuery()
    {
        return new Sw41Query($this->getModelManager());
    }

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
