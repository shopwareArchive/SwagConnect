<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\ShopwarePlugins\Connect;

use Shopware\Connect\Gateway\PDO;
use Shopware\Connect\Struct\Product;
use Shopware\Connect\Struct\ProductUpdate;
use Shopware\Connect\Struct\ShopConfiguration;
use Shopware\Models\ProductStream\ProductStream;
use ShopwarePlugins\Connect\Components\CategoryResolver\AutoCategoryResolver;
use ShopwarePlugins\Connect\Components\CategoryResolver\DefaultCategoryResolver;
use ShopwarePlugins\Connect\Components\Config;
use ShopwarePlugins\Connect\Components\ConfigFactory;
use ShopwarePlugins\Connect\Components\Gateway\ProductTranslationsGateway\PdoProductTranslationsGateway;
use ShopwarePlugins\Connect\Components\Marketplace\MarketplaceGateway;
use ShopwarePlugins\Connect\Components\ProductToShop;
use ShopwarePlugins\Connect\Components\VariantConfigurator;
use Shopware\Models\Article\Article;
use Shopware\Models\Property;
use Shopware\Models\Category\Category;
use Shopware\CustomModels\Connect\RemoteCategory;
use Shopware\CustomModels\Connect\ProductToRemoteCategory;
use ShopwarePlugins\Connect\Tests\DatabaseTestCaseTrait;

class ProductToShopTest extends ConnectTestHelper
{
    use DatabaseTestCaseTrait;

    /** @var \ShopwarePlugins\Connect\Components\ProductToShop */
    private $productToShop;

    /**
     * @var \Shopware\Components\Model\ModelManager
     */
    private $modelManager;

    private $db;

    /**
     * @var \Shopware\Connect\Gateway
     */
    private $gateway;

    public function tearDown()
    {
        $conn = Shopware()->Db();
        $conn->delete('s_plugin_connect_config', ['name = ?' => 'activateProductsAutomatically']);
        $conn->delete('s_plugin_connect_config', ['name = ?' => 'createUnitsAutomatically']);
        $conn->delete('s_plugin_connect_config', ['name = ?' => 'yd']);
        $conn->delete('s_plugin_connect_config', ['name = ?' => 'm']);
    }

    public function setUp()
    {
        parent::setUp();

        $this->db = Shopware()->Db();
        $this->db->delete('s_plugin_connect_config', ['name = ?' => 'activateProductsAutomatically']);
        $this->db->delete('s_plugin_connect_config', ['name = ?' => 'createUnitsAutomatically']);

        $this->gateway = new PDO($this->db->getConnection());

        $this->modelManager = Shopware()->Models();
        $this->productToShop = new ProductToShop(
            $this->getHelper(),
            $this->modelManager,
            $this->getImageImport(),
            ConfigFactory::getConfigInstance(),
            new VariantConfigurator(
                $this->modelManager,
                new PdoProductTranslationsGateway(Shopware()->Db())
            ),
            new MarketplaceGateway($this->modelManager),
            new PdoProductTranslationsGateway(Shopware()->Db()),
            new DefaultCategoryResolver(
                $this->modelManager,
                $this->modelManager->getRepository(RemoteCategory::class),
                $this->modelManager->getRepository(ProductToRemoteCategory::class),
                $this->modelManager->getRepository(Category::class)
            ),
            $this->gateway,
            Shopware()->Container()->get('events'),
            Shopware()->Container()->get('CategoryDenormalization')
        );
    }

    private function deleteVendorIfExists($vendorName)
    {
        $vendorCount = $this->db->query(
            'SELECT COUNT(id)
              FROM s_articles_supplier as supplier
              WHERE supplier.name = :supplierName',
            ['supplierName' => $vendorName]
        )->fetchColumn();

        if ($vendorCount) {
            Shopware()->Db()->delete('s_articles_supplier', ['name=?' => $vendorName]);
        }
    }

    public function truncateProperties()
    {
        $this->db->query('SET FOREIGN_KEY_CHECKS = 0');
        $this->db->query('TRUNCATE s_filter_articles');
        $this->db->query('TRUNCATE s_filter_relations');
        $this->db->query('TRUNCATE s_filter_attributes');
        $this->db->query('TRUNCATE s_filter');
        $this->db->query('TRUNCATE s_filter_options_attributes');
        $this->db->query('TRUNCATE s_filter_options');
        $this->db->query('TRUNCATE s_filter_values_attributes');
        $this->db->query('TRUNCATE s_filter_values');
        $this->db->query('SET FOREIGN_KEY_CHECKS = 1');
    }

    public function testInsertArticle()
    {
        $product = $this->getProduct();
        $product->minPurchaseQuantity = 5;
        $product->categories = [];

        $this->productToShop->insertOrUpdate($product);

        $articlesCount = Shopware()->Db()->query(
            'SELECT COUNT(s_articles.id)
              FROM s_plugin_connect_items
              LEFT JOIN s_articles ON (s_plugin_connect_items.article_id = s_articles.id)
              WHERE s_plugin_connect_items.source_id = :sourceId',
            ['sourceId' => $product->sourceId]
        )->fetchColumn();

        $this->assertEquals(1, $articlesCount);

        /** @var \Shopware\CustomModels\Connect\Attribute $connectAttribute */
        $connectAttribute = $this->modelManager
            ->getRepository('Shopware\CustomModels\Connect\Attribute')
            ->findOneBy(['sourceId' => $product->sourceId]);
        $detail = $connectAttribute->getArticleDetail();

        $this->assertEquals($product->minPurchaseQuantity, $detail->getMinPurchase());
        $this->assertNull(
            $detail->getAttribute()->getConnectMappedCategory(),
            'connect_mapped_category must be null when product does not contain mapped category'
        );
    }

    public function testInsertArticleTranslations()
    {
        $product = $this->getProduct();
        $this->productToShop->insertOrUpdate($product);
        $productRepository = Shopware()->Models()->getRepository('Shopware\Models\Article\Article');
        /** @var \Shopware\Models\Article\Article $productModel */
        $productModel = $productRepository->findOneBy(['name' => $product->title]);

        $articleTranslation = Shopware()->Db()->query(
            'SELECT objectdata
              FROM s_core_translations
              WHERE objectkey = :productId AND objectlanguage = 2 AND objecttype = :objectType',
            ['productId' => $productModel->getId(), 'objectType' => 'article']
        )->fetchColumn();

        $this->assertNotFalse($articleTranslation);
        $articleTranslation = unserialize($articleTranslation);

        $additionalDescription = array_key_exists(PdoProductTranslationsGateway::CONNECT_DESCRIPTION, $articleTranslation) ? $articleTranslation[PdoProductTranslationsGateway::CONNECT_DESCRIPTION] : '';

        $this->assertEquals($product->translations['en']->title, $articleTranslation['txtArtikel']);
        $this->assertEquals($product->translations['en']->longDescription, $articleTranslation['txtlangbeschreibung']);
        $this->assertEquals($product->translations['en']->shortDescription, $articleTranslation['txtshortdescription']);
        $this->assertEquals($product->translations['en']->additionalDescription, $additionalDescription);
    }

    public function testInsertVariantOptionsAndGroupsTranslations()
    {
        $variants = $this->getVariants();
        // insert variants
        foreach ($variants as $variant) {
            $this->productToShop->insertOrUpdate($variant);
        }

        $groupRepository = Shopware()->Models()->getRepository('Shopware\Models\Article\Configurator\Group');
        $optionRepository = Shopware()->Models()->getRepository('Shopware\Models\Article\Configurator\Option');
        foreach ($variants as $variant) {
            foreach ($variant->translations as $translation) {
                // check configurator group translations
                foreach ($translation->variantLabels as $groupKey => $groupTranslation) {
                    $group = $groupRepository->findOneBy(['name' => $groupKey]);

                    $objectData = Shopware()->Db()->query(
                        'SELECT objectdata
                          FROM s_core_translations
                          WHERE objectkey = :groupId AND objectlanguage = 2 AND objecttype = :objectType',
                        ['groupId' => $group->getId(), 'objectType' => 'configuratorgroup']
                    )->fetchColumn();

                    $objectData = unserialize($objectData);
                    $this->assertEquals($groupTranslation, $objectData['name']);
                }

                foreach ($translation->variantValues as $optionKey => $optionTranslation) {
                    $option =  $optionRepository->findOneBy(['name' => $optionKey]);
                    $objectData = Shopware()->Db()->query(
                        'SELECT objectdata
                          FROM s_core_translations
                          WHERE objectkey = :optionId AND objectlanguage = 2 AND objecttype = :objectType',
                        ['optionId' => $option->getId(), 'objectType' => 'configuratoroption']
                    )->fetchColumn();

                    $objectData = unserialize($objectData);
                    $this->assertEquals($optionTranslation, $objectData['name']);
                }
            }
        }
    }

    public function testInsertVariants()
    {
        $variants = $this->getVariants();

        foreach ($variants as $variant) {
            $this->productToShop->insertOrUpdate($variant);
        }

        $connectAttribute = $this->modelManager
            ->getRepository('Shopware\CustomModels\Connect\Attribute')
            ->findOneBy(['sourceId' => $variants[0]->sourceId]);
        $article = $connectAttribute->getArticle();
        // check articles details count
        $this->assertEquals(4, count($article->getDetails()));
        // check configurator set
        $this->assertNotNull($article->getConfiguratorSet());
        // check configurator group
        $group = $this->modelManager
            ->getRepository('Shopware\Models\Article\Configurator\Group')
            ->findOneBy(['name' => 'Farbe']);
        $this->assertNotNull($group);
        // check group options
        $groupOptionValues = $articleOptionValues = ['Weiss-Blau', 'Weiss-Rot', 'Blau-Rot', 'Schwarz-Rot'];
        foreach ($group->getOptions() as $option) {
            foreach ($groupOptionValues as $key => $groupOptionValue) {
                if (strpos($option->getName(), $groupOptionValue) == 0) {
                    unset($groupOptionValues[$key]);
                }
            }
        }
        $this->assertEmpty($groupOptionValues);
        // check configuration set options
        $this->assertEquals(4, count($article->getConfiguratorSet()->getOptions()));
        foreach ($article->getConfiguratorSet()->getOptions() as $option) {
            foreach ($articleOptionValues as $key => $articleOptionValue) {
                if (strpos($option->getName(), $articleOptionValue) == 0) {
                    unset($articleOptionValues[$key]);
                }
            }
        }
        $this->assertEmpty($articleOptionValues);
    }

    public function testUpdateVariant()
    {
        $variants = $this->getVariants();
        // insert variants
        foreach ($variants as $variant) {
            $this->productToShop->insertOrUpdate($variant);
        }

        $newTitle = 'Massimport#updateVariant' . rand(1, 10000000);
        $newPrice = 22.48;
        $newPurchasePrice = 8.48;
        $newLongDesc = 'Updated connect variant - long description';
        $newShortDesc = 'Updated connect variant - short description';
        $newVat = 0.07;
        $newSku = $variants[1]->sku . 'M';
        $variants[1]->title = $newTitle;
        $variants[1]->price = $newPrice;
        $variants[1]->purchasePrice = $newPurchasePrice;
        $variants[1]->longDescription = $newLongDesc;
        $variants[1]->shortDescription = $newShortDesc;
        $variants[1]->images[] = self::IMAGE_PROVIDER_URL . '?' . $variants[1]->sourceId;
        $variants[1]->variantImages[] = self::IMAGE_PROVIDER_URL . '?' . $variants[1]->sourceId;
        $variants[1]->vat = $newVat;
        $variants[1]->sku = $newSku;

        $this->productToShop->insertOrUpdate($variants[1]);

        /** @var \Shopware\CustomModels\Connect\Attribute $connectAttribute */
        $connectAttribute = $this->modelManager
            ->getRepository('Shopware\CustomModels\Connect\Attribute')
            ->findOneBy(['sourceId' => $variants[1]->sourceId]);
        $this->assertEquals($newTitle, $connectAttribute->getArticle()->getName());
        $this->assertEquals($newLongDesc, $connectAttribute->getArticle()->getDescriptionLong());
        $this->assertEquals($newShortDesc, $connectAttribute->getArticle()->getDescription());
        $detail = $connectAttribute->getArticleDetail();
        /** @var \Shopware\Models\Article\Price[] $prices */
        $prices = $detail->getPrices();

        $this->assertEquals($newPrice, $prices[0]->getPrice());
        if (method_exists($detail, 'getPurchasePrice')) {
            $this->assertEquals($newPurchasePrice, $detail->getPurchasePrice());
        } else {
            $this->assertEquals($newPurchasePrice, $prices[0]->getBasePrice());
        }

        $this->assertEquals(2, $connectAttribute->getArticle()->getImages()->count());
        $this->assertEquals(1, $detail->getImages()->count());
        $this->assertEquals(7.00, $connectAttribute->getArticle()->getTax()->getTax());
        $this->assertEquals('SC-3-' . $newSku, $detail->getNumber());
    }

    public function testImportWithoutTitle()
    {
        $product = new Product();
        $this->assertEmpty($this->productToShop->insertOrUpdate($product));
    }

    public function testImportWithoutVendor()
    {
        $product = new Product();
        $this->assertEmpty($this->productToShop->insertOrUpdate($product));
    }

    public function testDelete()
    {
        $variants = $this->getVariants();
        // insert variants
        foreach ($variants as $variant) {
            $this->productToShop->insertOrUpdate($variant);
        }

        // test delete only one variant
        $this->productToShop->delete($variants[1]->shopId, $variants[1]->sourceId);

        $connectAttribute = $this->modelManager
            ->getRepository('Shopware\CustomModels\Connect\Attribute')
            ->findOneBy(['sourceId' => $variants[2]->sourceId]);

        $article = $connectAttribute->getArticle();
        $this->modelManager->refresh($article);
        // check articles details count
        $this->assertEquals(3, count($article->getDetails()));

        // test delete article - main article variant
        $this->productToShop->delete($variants[0]->shopId, $variants[0]->sourceId);

        $articlesCount = Shopware()->Db()->query(
            'SELECT COUNT(s_articles.id)
              FROM s_plugin_connect_items
              LEFT JOIN s_articles ON (s_plugin_connect_items.article_id = s_articles.id)
              WHERE s_plugin_connect_items.source_id = :sourceId',
            ['sourceId' => $variants[0]->sourceId]
        )->fetchColumn();

        $this->assertEquals(0, $articlesCount);

        $attributesCount = Shopware()->Db()->query(
            'SELECT COUNT(s_plugin_connect_items.id)
              FROM s_plugin_connect_items
              WHERE s_plugin_connect_items.article_id = :articleId',
            ['articleId' => $article->getId()]
        )->fetchColumn();

        $this->assertEquals(2, $attributesCount);
    }

    public function testInsertPurchasePriceHash()
    {
        $product = $this->getProduct();
        $this->productToShop->insertOrUpdate($product);

        $articlesCount = Shopware()->Db()->query(
            'SELECT COUNT(s_articles.id)
              FROM s_plugin_connect_items
              LEFT JOIN s_articles ON (s_plugin_connect_items.article_id = s_articles.id)
              WHERE s_plugin_connect_items.purchase_price_hash = :purchasePriceHash
              AND s_plugin_connect_items.offer_valid_until = :offerValidUntil
              AND s_plugin_connect_items.source_id = :sourceId',
            [
                'purchasePriceHash' => $product->purchasePriceHash,
                'offerValidUntil' => $product->offerValidUntil,
                'sourceId' => $product->sourceId,
            ]
        )->fetchColumn();

        $this->assertEquals(1, $articlesCount);
    }

    public function testUpdate()
    {
        $product = $this->getProduct();
        $this->productToShop->insertOrUpdate($product);

        $articlesCount = Shopware()->Db()->query(
            'SELECT COUNT(s_articles.id)
              FROM s_plugin_connect_items
              LEFT JOIN s_articles ON (s_plugin_connect_items.article_id = s_articles.id)
              WHERE s_plugin_connect_items.source_id = :sourceId',
            ['sourceId' => $product->sourceId]
        )->fetchColumn();

        $this->assertEquals(1, $articlesCount);

        $purchasePrice = 8.99;
        $offerValidUntil = time() + 1 * 365 * 24 * 60 * 60; // One year
        $productUpdate = new ProductUpdate([
            'price' => 10.99,
            'purchasePrice' => $purchasePrice,
            'purchasePriceHash' => hash_hmac(
                'sha256',
                sprintf('%.3F %d', $purchasePrice, $offerValidUntil), '54642546-0001-48ee-b4d0-4f54af66d822'
            ),
            'offerValidUntil' => $offerValidUntil,
            'availability' => 80,
        ]);

        $this->productToShop->update($product->shopId, $product->sourceId, $productUpdate);

        /** @var \Shopware\CustomModels\Connect\Attribute $connectAttribute */
        $connectAttribute = $this->modelManager
            ->getRepository('Shopware\CustomModels\Connect\Attribute')
            ->findOneBy(['sourceId' => $product->sourceId]);

        $this->assertEquals($productUpdate->purchasePriceHash, $connectAttribute->getPurchasePriceHash());
        $this->assertEquals($productUpdate->offerValidUntil, $connectAttribute->getOfferValidUntil());
        $this->assertEquals($productUpdate->purchasePrice, $connectAttribute->getPurchasePrice());

        $this->assertEquals($productUpdate->availability, $connectAttribute->getArticleDetail()->getInStock());
        $detail = $connectAttribute->getArticleDetail();
        /** @var \Shopware\Models\Article\Price[] $prices */
        $prices = $detail->getPrices();
        $this->assertEquals($productUpdate->price, $prices[0]->getPrice());
        if (method_exists($detail, 'getPurchasePrice')) {
            $this->assertEquals($productUpdate->purchasePrice, $detail->getPurchasePrice());
        } else {
            $this->assertEquals($productUpdate->purchasePrice, $prices[0]->getBasePrice());
        }
    }

    public function testChangeAvailability()
    {
        $product = $this->getProduct();
        $this->productToShop->insertOrUpdate($product);

        $articlesCount = Shopware()->Db()->query(
            'SELECT COUNT(s_articles.id)
              FROM s_plugin_connect_items
              LEFT JOIN s_articles ON (s_plugin_connect_items.article_id = s_articles.id)
              WHERE s_plugin_connect_items.source_id = :sourceId',
            ['sourceId' => $product->sourceId]
        )->fetchColumn();

        $this->assertEquals(1, $articlesCount);

        $newAvailability = 20;
        $this->productToShop->changeAvailability($product->shopId, $product->sourceId, $newAvailability);

        /** @var \Shopware\CustomModels\Connect\Attribute $connectAttribute */
        $connectAttribute = $this->modelManager
            ->getRepository('Shopware\CustomModels\Connect\Attribute')
            ->findOneBy(['sourceId' => $product->sourceId]);

        $this->assertEquals($newAvailability, $connectAttribute->getArticleDetail()->getInStock());
    }

    public function testMakeMainVariant()
    {
        $variants = $this->getVariants();

        foreach ($variants as $variant) {
            $this->productToShop->insertOrUpdate($variant);
        }

        $this->productToShop->makeMainVariant($variants[3]->shopId, $variants[3]->sourceId, null);

        /** @var \Shopware\CustomModels\Connect\Attribute $connectAttribute */
        $connectAttribute = $this->modelManager
            ->getRepository('Shopware\CustomModels\Connect\Attribute')
            ->findOneBy(['sourceId' => $variants[3]->sourceId]);

        $this->assertEquals(1, $connectAttribute->getArticleDetail()->getKind());
        $this->assertEquals(
            $connectAttribute->getArticleDetailId(),
            $connectAttribute->getArticle()->getMainDetail()->getId()
        );
    }

    public function testInsertArticleAndAutomaticallyCreateCategories()
    {
        $config =  ConfigFactory::getConfigInstance();
        $productToShop = new ProductToShop(
            $this->getHelper(),
            $this->modelManager,
            $this->getImageImport(),
            $config,
            new VariantConfigurator(
                $this->modelManager,
                new PdoProductTranslationsGateway(Shopware()->Db())
            ),
            new MarketplaceGateway($this->modelManager),
            new PdoProductTranslationsGateway(Shopware()->Db()),
            new AutoCategoryResolver(
                $this->modelManager,
                $this->modelManager->getRepository(Category::class),
                $this->modelManager->getRepository(RemoteCategory::class),
                $config,
                $this->modelManager->getRepository(ProductToRemoteCategory::class)
            ),
            $this->gateway,
            Shopware()->Container()->get('events'),
            Shopware()->Container()->get('CategoryDenormalization')
        );

        $product = $this->getProduct();
        $parentCategory1 = 'Deutsch';
        $childCategory = 'MassImport#' . rand(1, 999999999);
        $childCategory2 = 'MassImport#' . rand(1, 999999999);
        $parentCategory2 = 'MassImport#' . rand(1, 999999999);
        // add custom categories
        $product->categories = [
            '/' . strtolower($parentCategory1) => $parentCategory1,
            '/' . strtolower($parentCategory1) . '/' . strtolower($childCategory) => $childCategory,
            '/' . strtolower($parentCategory1) . '/' . strtolower($childCategory) . '/' . strtolower($childCategory2) => $childCategory2,
            '/' . strtolower($parentCategory2) => $parentCategory2,
        ];
        foreach ($product->categories as $key => $value) {
            $this->modelManager->getConnection()->executeQuery('INSERT IGNORE INTO `s_plugin_connect_categories` (`category_key`, `label`) 
              VALUES (?, ?)',
                [$key, $value]);
        }

        $productToShop->insertOrUpdate($product);

        $categoryRepository = Shopware()->Models()->getRepository('Shopware\Models\Category\Category');
        /** @var \Shopware\Models\Category\Category $childCategoryModel */
        $childCategoryModel = $categoryRepository->findOneBy(['name' => $childCategory]);

        $this->assertInstanceOf('Shopware\Models\Category\Category', $childCategoryModel);
        $this->assertEquals(
            $config->getDefaultShopCategory()->getName(),
            $childCategoryModel->getParent()->getName()
        );

        $categoryRepository = Shopware()->Models()->getRepository(Category::class);
        /** @var \Shopware\Models\Category\Category $childCategoryModel */
        $childCategoryModel2 = $categoryRepository->findOneBy(['name' => $childCategory2]);
        $this->assertInstanceOf(Category::class, $childCategoryModel2);
        $this->assertEquals(
            $childCategoryModel->getName(),
            $childCategoryModel2->getParent()->getName()
        );
        $this->assertEquals(
            $childCategory2,
            $childCategoryModel2->getName()
        );

        /** @var Article $article */
        $article = $this->modelManager->getRepository(Article::class)->findOneByName($product->title);

        $assignCategories = $article->getCategories();
        $this->assertEquals(1, count($assignCategories));
        $this->assertEquals($childCategory2, $assignCategories[0]->getName());
        $this->assertEquals(1, $article->getAttribute()->getConnectMappedCategory());

        foreach ($product->categories as $key => $value) {
            $this->modelManager->getConnection()->executeQuery('DELETE FROM `s_plugin_connect_categories`
              WHERE `category_key` = ? AND `label` = ?',
                [$key, $value]);
        }
    }

    public function testAutomaticallyCreateUnits()
    {
        $conn = Shopware()->Db();
        $conn->insert('s_plugin_connect_config', [
            'name' => 'createUnitsAutomatically',
            'value' => '1'
        ]);
        $product = $this->getProduct();
        $unit = 'yd';
        $product->attributes['unit'] = $unit;
        $product->attributes['quantity'] = 1;
        $product->attributes['ref_quantity'] = 5;

        $this->productToShop->insertOrUpdate($product);

        /** @var \Shopware\Models\Article\Article $article */
        $article = $this->modelManager->getRepository('Shopware\Models\Article\Article')->findOneBy([
            'name' => $product->title
        ]);
        $this->assertInstanceOf('Shopware\Models\Article\Article', $article);
        $this->assertInstanceOf('Shopware\Models\Article\Unit', $article->getMainDetail()->getUnit());
        $this->assertEquals('yd', $article->getMainDetail()->getUnit()->getUnit());
        $this->assertEquals('Yard', $article->getMainDetail()->getUnit()->getName());
    }

    /**
     * Connect units must be stored in config table
     * during product import
     *
     * @throws \Zend_Db_Statement_Exception
     */
    public function testStoreUnitsOnProductImport()
    {
        $product = $this->getProduct();
        $unit = 'm';
        $product->attributes['unit'] = $unit;
        $product->attributes['quantity'] = 1;
        $product->attributes['ref_quantity'] = 5;

        $this->productToShop->insertOrUpdate($product);

        $query = Shopware()->Db()->query(
            'SELECT COUNT(id)
              FROM s_plugin_connect_config
              WHERE `name` = :configName
              AND groupName = :groupName',
            ['configName' => 'm', 'groupName' => 'units']
        );

        $this->assertEquals(1, $query->fetchColumn());
    }

    public function testInsertArticleVendorArray()
    {
        $product = $this->getProduct();
        $this->deleteVendorIfExists($product->vendor['name']);

        $this->productToShop->insertOrUpdate($product);

        $supplier = $this->db->query(
            'SELECT *
              FROM s_articles_supplier as supplier
              WHERE supplier.name = :supplierName',
            ['supplierName' => $product->vendor['name']]
        )->fetchObject();

        $this->assertEquals($product->vendor['name'], $supplier->name);
        $this->assertEquals($product->vendor['url'], $supplier->link);
        $this->assertEquals($product->vendor['description'], $supplier->description);
        $this->assertEquals($product->vendor['page_title'], $supplier->meta_title);
    }

    public function testInsertArticleVendorString()
    {
        $product = $this->getProduct();
        $product->vendor = 'shopware Connect';
        $this->deleteVendorIfExists($product->vendor);

        $this->productToShop->insertOrUpdate($product);
        $supplier = $this->db->query(
            'SELECT *
              FROM s_articles_supplier as supplier
              WHERE supplier.name = :supplierName',
            ['supplierName' => $product->vendor]
        )->fetchObject();

        $supplierAttr = $this->db->query(
            'SELECT *
              FROM s_articles_supplier_attributes as sa
              WHERE sa.supplierID = :supplierId',
            ['supplierId' => $supplier->id]
        )->fetchObject();

        $this->assertEquals($product->vendor, $supplier->name);
        $this->assertEquals(1, $supplierAttr->connect_is_remote);
    }

    public function testInsertArticleProperties()
    {
        $product = $this->getProduct();
        $product->properties = $this->getProperties();

        $this->truncateProperties();

        $this->productToShop->insertOrUpdate($product);

        /** @var Article $article */
        $article = $this->modelManager->getRepository(Article::class)->findOneBy([
            'name' => $product->title
        ]);

        /** @var \Shopware\Connect\Struct\Property $firstProperty */
        $firstProperty = reset($product->properties);

        /** @var Property\Group $group */
        $group = $this->modelManager->getRepository(Property\Group::class)->findOneBy([
            'name' => $firstProperty->groupName
        ]);

        $this->assertEquals(3, count($article->getPropertyValues()));
        $this->assertEquals(2, count($group->getOptions()));
        $this->assertEquals($firstProperty->groupName, $article->getPropertyGroup()->getName());

        /** @var Property\Value $propertyValue */
        foreach ($article->getPropertyValues() as $index => $propertyValue) {
            $property = $product->properties[$index];
            $this->assertEquals($property->value, $propertyValue->getValue());
            $this->assertEquals($property->valuePosition, $propertyValue->getPosition());
        }

        /** @var Property\Option $option */
        foreach ($group->getOptions() as $index => $option) {
            $property = $product->properties[$index];
            $this->assertEquals($property->option, $option->getName());
        }
    }

    public function testCreateStreamAndAddProductToStream()
    {
        $product = $this->getProduct();
        $this->productToShop->insertOrUpdate($product);

        /** @var Article $article */
        $article = $this->modelManager->getRepository(Article::class)->findOneBy([
            'name' => $product->title
        ]);

        /** @var ProductStream $stream */
        $stream = $this->modelManager->getRepository(ProductStream::class)->findOneBy(['name' => $product->stream]);
        $this->assertNotNull($stream);
        $this->assertEquals(1, $stream->getAttribute()->getConnectIsRemote());

        $connection = $this->modelManager->getConnection();
        $builder = $connection->createQueryBuilder();
        $builder->select('*')
            ->from('s_product_streams_selection')
            ->where('stream_id = :streamId')
            ->andWhere('article_id = :articleId')
            ->setParameter('streamId', $stream->getId())
            ->setParameter('articleId', $article->getId());

        $this->assertNotEmpty($builder->execute()->fetchAll());
    }

    public function testAutomaticallyActivateArticles()
    {
        $conn = Shopware()->Db();
        $conn->insert('s_plugin_connect_config', [
            'name' => 'activateProductsAutomatically',
            'value' => '1',
            'groupName' => 'general',
        ]);

        $product = $this->getProduct();
        $this->productToShop->insertOrUpdate($product);
        /** @var \Shopware\Models\Article\Article $article */
        $article = $this->modelManager->getRepository('Shopware\Models\Article\Article')->findOneBy([
            'name' => $product->title
        ]);
        $this->assertInstanceOf('Shopware\Models\Article\Article', $article);
        $this->assertInstanceOf('Shopware\Models\Article\Detail', $article->getMainDetail());
        $this->assertTrue($article->getActive(), 'Article is activated');
        $this->assertEquals(1, $article->getMainDetail()->getActive(), 'Detail is activated');
    }

    public function testInsertArticleWithSellNotInStock()
    {
        $product = $this->getProduct();

        $shopConfiguration = new ShopConfiguration();
        $shopConfiguration->sellNotInStock = true;

        $this->gateway->setShopConfiguration($product->shopId, $shopConfiguration);

        $this->productToShop->insertOrUpdate($product);
        /** @var \Shopware\Models\Article\Article $article */
        $article = $this->modelManager->getRepository('Shopware\Models\Article\Article')->findOneBy([
            'name' => $product->title
        ]);

        $this->assertFalse($article->getLastStock());
    }

    /**
     * Test inserting variant with same values (Black for example)
     * for 1st and 2nd color
     */
    public function testInsertVariantWithSameValues()
    {
        $variants = $this->getVariants();
        // duplicate color value
        $variants[0]->variant['Farbe2'] = $variants[0]->variant['Farbe'];
        // insert variants
        foreach ($variants as $variant) {
            $this->productToShop->insertOrUpdate($variant);
        }

        $group = $this->modelManager
            ->getRepository('Shopware\Models\Article\Configurator\Group')
            ->findOneBy(['name' => 'Farbe']);
        $this->assertNotNull($group);

        $group2 = $this->modelManager
            ->getRepository('Shopware\Models\Article\Configurator\Group')
            ->findOneBy(['name' => 'Farbe2']);
        $this->assertNotNull($group2);

        // check group options
        $colorValue = null;
        foreach ($group->getOptions() as $option) {
            if ($option->getName() == $variants[0]->variant['Farbe']) {
                $colorValue = $variants[0]->variant['Farbe2'];
            }
        }

        $colorValue2 = null;
        foreach ($group2->getOptions() as $option) {
            if ($option->getName() == $variants[0]->variant['Farbe2']) {
                $colorValue2 = $variants[0]->variant['Farbe2'];
            }
        }

        $this->assertNotNull($colorValue);
        $this->assertNotNull($colorValue2);
        $this->assertEquals($colorValue, $colorValue2);
        $this->assertEquals(0, strpos($colorValue, 'Schwarz-Rot'));
        $this->assertEquals(0, strpos($colorValue2, 'Schwarz-Rot'));
    }
}
