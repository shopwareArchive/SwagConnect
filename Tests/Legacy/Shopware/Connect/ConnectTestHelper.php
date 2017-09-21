<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\ShopwarePlugins\Connect;

use Shopware\Connect\Gateway\PDO;
use Shopware\Connect\SDK;
use Shopware\Connect\Struct\Product;
use Shopware\Connect\Struct\Property;
use Shopware\Connect\Struct\Translation;
use ShopwarePlugins\Connect\Components\CategoryResolver\DefaultCategoryResolver;
use ShopwarePlugins\Connect\Components\ConfigFactory;
use ShopwarePlugins\Connect\Components\ConnectExport;
use ShopwarePlugins\Connect\Components\ConnectFactory;
use ShopwarePlugins\Connect\Components\ErrorHandler;
use ShopwarePlugins\Connect\Components\Gateway\ProductTranslationsGateway\PdoProductTranslationsGateway;
use ShopwarePlugins\Connect\Components\ImageImport;
use ShopwarePlugins\Connect\Components\Logger;
use ShopwarePlugins\Connect\Components\Marketplace\MarketplaceGateway;
use ShopwarePlugins\Connect\Components\ProductToShop;
use ShopwarePlugins\Connect\Components\Validator\ProductAttributesValidator\ProductsAttributesValidator;
use Shopware\CustomModels\Connect\Attribute;
use Shopware\Models\Article\Article;
use Shopware\Models\Article\Detail;
use ShopwarePlugins\Connect\Components\VariantConfigurator;

class ConnectTestHelper extends \Enlight_Components_Test_Plugin_TestCase
{
    /**
     * @var SDK
     */
    private $sdk;

    /**
     * @var ConnectFactory
     */
    private $connectFactory;

    const IMAGE_PROVIDER_URL = 'http://www.shopware.de/ShopwareCommunityCenter/img/logo.png';

    public function setUp()
    {
        parent::setUp();

        set_error_handler(null);
        set_exception_handler(null);
    }

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
     * @param string $class
     * @param string $method
     * @param array $args
     * @return mixed
     */
    public function callPrivate($class, $method, $args)
    {
        $method = new \ReflectionMethod(
            $class, $method
        );

        $method->setAccessible(true);

        return call_user_func([$method, 'invoke', $args]);
    }

    /**
     * @return ConnectExport
     */
    public function getConnectExport()
    {
        return new ConnectExport(
            $this->getHelper(),
            $this->getSDK(),
            Shopware()->Models(),
            new ProductsAttributesValidator(),
            ConfigFactory::getConfigInstance(),
            new ErrorHandler(),
            Shopware()->Container()->get('events')
        );
    }

    /**
     * @return ImageImport
     */
    public function getImageImport()
    {
        return new ImageImport(
            Shopware()->Models(),
            $this->getHelper(),
            Shopware()->Container()->get('thumbnail_manager'),
            new Logger(Shopware()->Db())
        );
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
                'logo_url' => self::IMAGE_PROVIDER_URL,
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
            $product->images = [self::IMAGE_PROVIDER_URL . '?' . $number];
        }

        if ($withVariantImages) {
            $product->variantImages = [self::IMAGE_PROVIDER_URL . '?' . $number . '-variantImage'];
        }

        return $product;
    }

    /**
     * @return array
     */
    protected function getProperties()
    {
        return [
            new Property([
                'groupName' => 'Nike',
                'comparable' => false,
                'sortMode' => 1,
                'option' => 'color',
                'filterable' => false,
                'value' => 'red'
            ]),
            new Property([
                'groupName' => 'Nike',
                'comparable' => false,
                'sortMode' => 1,
                'option' => 'size',
                'filterable' => false,
                'value' => 'XXL',
                'valuePosition' => 1
            ]),
            new Property([
                'groupName' => 'Nike',
                'comparable' => false,
                'sortMode' => 1,
                'option' => 'size',
                'filterable' => false,
                'value' => '3XL'
            ])
        ];
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
     * @return array
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
        $mainVariant->configuratorSetType = 1;
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
     * @return Article
     */
    public function getLocalArticle()
    {
        $number = rand(1, 999999999);

        $article = new Article();
        $article->fromArray([
            'name' => 'LocalArticle #' . $number,
            'active' => true,
        ]);
        $tax = Shopware()->Models()->getRepository('Shopware\Models\Tax\Tax')->find(1);
        $article->setTax($tax);

        $supplier = Shopware()->Models()->getRepository('Shopware\Models\Article\Supplier')->find(1);
        $article->setSupplier($supplier);

        Shopware()->Models()->persist($article);
        Shopware()->Models()->flush();

        $mainDetail = new Detail();
        $mainDetail->fromArray([
            'number' => $number,
            'inStock' => 30,
            'article' => $article
        ]);
        $article->setMainDetail($mainDetail);
        $detailAtrribute = new \Shopware\Models\Attribute\Article();
        $detailAtrribute->fromArray([
            'article' => $article,
            'articleDetail' => $mainDetail,
        ]);

        /** @var \Shopware\Models\Customer\Group $customerGroup */
        $customerGroup = Shopware()->Models()->getRepository('Shopware\Models\Customer\Group')->findOneByKey('EK');

        $connectAttribute = new Attribute();
        $connectAttribute->fromArray([
            'isMainVariant' => true,
            'article' => $article,
            'articleDetail' => $article->getMainDetail(),
            'sourceId' => $article->getId(),
            'category' => '/bücher',
            'fixedPrice' => false,
            'purchasePriceHash' => '',
            'offerValidUntil' => 0,
            'stream' => '',
        ]);

        Shopware()->Models()->persist($mainDetail);
        Shopware()->Models()->persist($detailAtrribute);
        Shopware()->Models()->persist($connectAttribute);
        Shopware()->Models()->flush();

        // set price via plain SQL because shopware throws exception
        // undefined index: key when error handler is disabled
        Shopware()->Db()->executeQuery(
            'INSERT INTO `s_articles_prices`(`pricegroup`, `from`, `to`, `articleID`, `articledetailsID`, `price`, `baseprice`)
          VALUES (?, 1, "beliebig", ?, ?, ?, ?)
          ', [$customerGroup->getKey(), $article->getId(), $mainDetail->getId(), 8.99, 3.99]);

        return $article;
    }

    /**
     * @return ProductToShop
     */
    public function getProductToShop()
    {
        $manager = Shopware()->Models();

        return new ProductToShop(
            $this->getHelper(),
            Shopware()->Models(),
            $this->getImageImport(),
            ConfigFactory::getConfigInstance(),
            new VariantConfigurator(
                $manager,
                new PdoProductTranslationsGateway(Shopware()->Db())
            ),
            new MarketplaceGateway($manager),
            new PdoProductTranslationsGateway(Shopware()->Db()),
            new DefaultCategoryResolver(
                $manager,
                $manager->getRepository('Shopware\CustomModels\Connect\RemoteCategory'),
                $manager->getRepository('Shopware\CustomModels\Connect\ProductToRemoteCategory'),
                $manager->getRepository('Shopware\Models\Category\Category')
            ),
            new PDO(Shopware()->Db()->getConnection()),
            Shopware()->Container()->get('events'),
            Shopware()->Container()->get('CategoryDenormalization')
        );
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
     * @return array
     */
    protected function getRandomUser()
    {
        $user = Shopware()->Db()->fetchRow('SELECT * FROM s_user WHERE id = 1 LIMIT 1');

        $billing = Shopware()->Db()->fetchRow(
            'SELECT * FROM s_user_billingaddress WHERE userID = :id',
            [':id' => $user['id']]
        );
        $billing['stateID'] = isset($billing['stateId']) ? $billing['stateID'] : '1';
        $shipping = Shopware()->Db()->fetchRow(
            'SELECT * FROM s_user_shippingaddress WHERE userID = :id',
            [':id' => $user['id']]
        );
        $shipping['stateID'] = isset($shipping['stateId']) ? $shipping['stateID'] : '1';
        $country = Shopware()->Db()->fetchRow(
            'SELECT * FROM s_core_countries WHERE id = :id',
            [':id' => $billing['countryID']]
        );
        $state = Shopware()->Db()->fetchRow(
            'SELECT * FROM s_core_countries_states WHERE id = :id',
            [':id' => $billing['stateID']]
        );
        $countryShipping = Shopware()->Db()->fetchRow(
            'SELECT * FROM s_core_countries WHERE id = :id',
            [':id' => $shipping['countryID']]
        );
        $payment = Shopware()->Db()->fetchRow(
            'SELECT * FROM s_core_paymentmeans WHERE id = :id',
            [':id' => $user['paymentID']]
        );
        $customerGroup = Shopware()->Db()->fetchRow(
            'SELECT * FROM s_core_customergroups WHERE groupkey = :key',
            [':key' => $user['customergroup']]
        );

        $taxFree = (bool) ($countryShipping['taxfree']);
        if ($countryShipping['taxfree_ustid']) {
            if ($countryShipping['id'] == $country['id'] && $billing['ustid']) {
                $taxFree = true;
            }
        }

        if ($taxFree) {
            $customerGroup['tax'] = 0;
        }

        Shopware()->Session()->sUserGroupData = $customerGroup;

        return [
            'user' => $user,
            'billingaddress' => $billing,
            'shippingaddress' => $shipping,
            'customerGroup' => $customerGroup,
            'additional' => [
                'country' => $country,
                'state'   => $state,
                'user'    => $user,
                'countryShipping' => $countryShipping,
                'payment' => $payment,
                'charge_vat' => !$taxFree
            ]
        ];
    }
}
