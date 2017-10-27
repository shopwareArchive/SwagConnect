<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Tests;

use Shopware\Connect\SDK;
use Shopware\Connect\Struct\Product;
use Shopware\Connect\Struct\Translation;
use ShopwarePlugins\Connect\Components\ConnectFactory;

trait ConnectTestHelperTrait
{
    private $IMAGE_PROVIDER_URL = 'http://www.shopware.de/ShopwareCommunityCenter/img/logo.png';

    private $connectFactory;
    private $sdk;

    /**
     * @return ConnectFactory
     */
    public function getConnectFactory()
    {
        if (!$this->connectFactory) {
            $this->connectFactory = new ConnectFactory();
        }

        return $this->connectFactory;
    }

    /**
     * @return SDK
     */
    public function getSDK()
    {
        if (!$this->sdk) {
            $this->sdk = $this->getConnectFactory()->createSdk();
        }

        return $this->sdk;
    }

    /**
     * @return \ShopwarePlugins\Connect\Components\Helper
     */
    public function getHelper()
    {
        return Shopware()->Plugins()->Backend()->SwagConnect()->getHelper();
    }

    /**
     * @param string $number
     * @param bool $withImage
     * @param bool $withVariantImages
     * @return array
     */
    protected function insertOrUpdateProducts($number, $withImage, $withVariantImages)
    {
        $commands = [];
        foreach ($this->getProducts($number, $withImage, $withVariantImages) as $product) {
            $commands[$product->sourceId] = new \Shopware\Connect\Struct\Change\ToShop\InsertOrUpdate([
                'product' => $product,
                'revision' => time(),
            ]);
        }

        $this->dispatchRpcCall('products', 'toShop', [
            $commands
        ]);

        return array_keys($commands);
    }

    /**
     * @param int $number
     * @param bool $withImage
     * @param bool $withVariantImages
     * @return array
     */
    protected function getProducts($number = 10, $withImage = false, $withVariantImages = false)
    {
        $products = [];
        for ($i=0; $i<$number; ++$i) {
            $products[] = $this->getProduct($withImage, $withVariantImages);
        }

        return $products;
    }

    /**
     * @param bool $withImage
     * @param bool $withVariantImages
     * @return Product
     */
    protected function getProduct($withImage = false, $withVariantImages = false)
    {
        $purchasePrice = 6.99;
        $offerValidUntil = time() + 1 * 365 * 24 * 60 * 60; // One year
        $number = rand(1, 999999999);
        $product =  new Product([
            'shopId' => 3,
            'revisionId' => time(),
            'sourceId' => $number,
            'ean' => $number,
            'sku' => 'sku#' . $number,
            'url' => 'http://shopware.de',
            'title' => 'MassImport #' . $number,
            'shortDescription' => 'Ein Produkt aus shopware Connect',
            'longDescription' => 'Ein Produkt aus shopware Connect',
            'additionalDescription' => 'Ein Produkt aus shopware Connect',
            'vendor' => [
                'url' => 'http://connect.shopware.de/',
                'name' => 'shopware Connect',
                'logo_url' => $this->IMAGE_PROVIDER_URL,
                'page_title' => 'shopware Connect title',
                'description' => 'shopware Connect description'
            ],
            'stream' => 'Awesome products',
            'price' => 9.99,
            'purchasePrice' => $purchasePrice,
            'purchasePriceHash' => hash_hmac(
                'sha256',
                sprintf('%.3F %d', $purchasePrice, $offerValidUntil), '54642546-0001-48ee-b4d0-4f54af66d822'
            ),
            'offerValidUntil' => $offerValidUntil,
            'availability' => 100,
            'categories' => [
                '/deutsch/bücher' => 'Bücher',
                '/deutsch' => 'Deutsch',
            ],
            'translations' => [
                'en' => new Translation([
                    'title' => 'MassImport #' . $number . ' EN',
                    'longDescription' => 'Ein Produkt aus shopware Connect EN',
                    'shortDescription' => 'Ein Produkt aus shopware Connect short EN',
                    'additionalDescription' => 'Ein Produkt aus shopware Verbinden Sie mit zusätzlicher Beschreibung EN',
                    'url' => 'http://shopware.de',
                ])
            ]
        ]);

        if ($withImage) {
            $product->images = [$this->IMAGE_PROVIDER_URL . '?' . $number];
        }

        if ($withVariantImages) {
            $product->variantImages = [$this->IMAGE_PROVIDER_URL . '?' . $number . '-variantImage'];
        }

        return $product;
    }

    /**
     * @param string $service
     * @param string $command
     * @param array $args
     * @return mixed
     */
    public static function dispatchRpcCall($service, $command, array $args)
    {
        $sdk = Shopware()->Container()->get('ConnectSDK');
        $refl = new \ReflectionObject($sdk);
        $property = $refl->getProperty('dependencies');
        $property->setAccessible(true);
        $deps = $property->getValue($sdk);
        $serviceRegistry = $deps->getServiceRegistry();
        $callable = $serviceRegistry->getService($service, $command);

        return call_user_func_array([$callable['provider'], $callable['command']], $args);
    }
}
