<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Tests;

use Shopware\Connect\Struct\Change\ToShop\InsertOrUpdate;
use Shopware\Connect\Struct\Product;
use Shopware\Connect\Struct\Translation;

trait ProductBuilderTrait
{
    /**
     * @var string
     */
    private $imageProviderUrl = 'http://www.shopware.de/ShopwareCommunityCenter/img/logo.png';

    /**
     * @param bool $withImage
     * @param bool $withVariantImages
     * @return Product
     */
    public function getProduct($withImage = false, $withVariantImages = false)
    {
        $purchasePrice = 6.99;
        $offerValidUntil = time() + 1 * 365 * 24 * 60 * 60; // One year
        $number = rand(1, 999999999);
        $product =  new Product([
            'shopId' => 3,
            'revisionId' => time(),
            'sourceId' => (string) $number,
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
                'logo_url' => $this->imageProviderUrl,
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
                '/bücher' => 'Bücher',
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
            $product->images = [$this->imageProviderUrl . '?' . $number];
        }

        if ($withVariantImages) {
            $product->variantImages = [$this->imageProviderUrl . '?' . $number . '-variantImage'];
        }

        return $product;
    }

    /**
     * @return Product[]
     */
    protected function getVariants()
    {
        $number = $groupId = rand(1, 999999999);
        $color = [
            ['de' => 'Weiss-Blau' . $number, 'en' => 'White-Blue'],
            ['de' => 'Weiss-Rot' . $number, 'en' => 'White-Red'],
            ['de' => 'Blau-Rot' . $number, 'en' => 'Blue-Red'],
            ['de' => 'Schwarz-Rot' . $number, 'en' => 'Black-Red'],
        ];

        $variants = [];
        $mainVariant = $this->getProduct(true);
        $mainVariantColor = array_pop($color);
        $mainVariant->variant['Farbe'] = $mainVariantColor['de'];
        $mainVariant->groupId = $groupId;
        $variants[] = $mainVariant;

        //add translations
        $mainVariant->translations['en']->variantLabels = [
            'Farbe' => 'Color',
        ];
        $mainVariant->translations['en']->variantValues = [
            $mainVariantColor['de'] => $mainVariantColor['en'],
        ];

        for ($i = 0; $i < 4 - 1; ++$i) {
            $variant = $this->getProduct(true);
            $variantSourceId = $mainVariant->sourceId . '-' . $i;
            $variant->title = 'MassImport #' . $variantSourceId;
            $variant->sourceId = $variantSourceId;
            $variant->ean = $variantSourceId;
            $variantColor = array_pop($color);
            $variant->variant['Farbe'] = $variantColor['de'];
            $variant->groupId = $groupId;
            $variant->translations = [
                'en' => new Translation([
                    'title' => 'MassImport #' . $variantSourceId . ' EN',
                    'longDescription' => $mainVariant->longDescription . ' EN',
                    'shortDescription' => $mainVariant->shortDescription . ' EN',
                    'variantLabels' => [
                        'Farbe' => 'Color',
                    ],
                    'variantValues' => [
                        $variantColor['de'] => $variantColor['en'],
                    ],
                ]),
            ];

            $variants[] = $variant;
        }

        return $variants;
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
     * @param string $number
     * @param bool $withImage
     * @param bool $withVariantImages
     * @return array
     */
    protected function insertOrUpdateProducts($number, $withImage, $withVariantImages)
    {
        $commands = [];
        foreach ($this->getProducts($number, $withImage, $withVariantImages) as $product) {
            $commands[$product->sourceId] = new InsertOrUpdate([
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
