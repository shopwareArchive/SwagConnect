<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Tests\Integration\Components;

use Shopware\Connect\Gateway\PDO;
use ShopwarePlugins\Connect\Components\CategoryResolver\DefaultCategoryResolver;
use ShopwarePlugins\Connect\Components\ConfigFactory;
use ShopwarePlugins\Connect\Components\ConnectFactory;
use ShopwarePlugins\Connect\Components\Gateway\ProductTranslationsGateway\PdoProductTranslationsGateway;
use ShopwarePlugins\Connect\Components\ImageImport;
use ShopwarePlugins\Connect\Components\Logger;
use ShopwarePlugins\Connect\Components\Marketplace\MarketplaceGateway;
use ShopwarePlugins\Connect\Components\ProductToShop;
use ShopwarePlugins\Connect\Components\VariantConfigurator;
use Shopware\CustomModels\Connect\RemoteCategory;
use Shopware\CustomModels\Connect\ProductToRemoteCategory;
use Shopware\Components\Model\ModelManager;
use ShopwarePlugins\Connect\Tests\DatabaseTestCaseTrait;
use ShopwarePlugins\Connect\Tests\ProductBuilderTrait;
use Shopware\Models\Category\Category;

class ProductToShopTest extends \PHPUnit_Framework_TestCase
{
    use DatabaseTestCaseTrait;

    use ProductBuilderTrait;

    /**
     * @var ModelManager
     */
    private $manager;

    /**
     * @var \Enlight_Components_Db_Adapter_Pdo_Mysql
     */
    private $db;

    /**
     * @var ProductToShop
     */
    private $productToShop;

    /**
     * @before
     */
    public function prepare()
    {
        $this->manager = Shopware()->Models();
        $connectFactory = new ConnectFactory();
        $helper = $connectFactory->getHelper();
        $this->db = Shopware()->Db();

        $this->productToShop = new ProductToShop(
            $helper,
            $this->manager,
            new ImageImport(
                $this->manager,
                $helper,
                Shopware()->Container()->get('thumbnail_manager'),
                new Logger($this->db)
            ),
            ConfigFactory::getConfigInstance(),
            new VariantConfigurator(
                $this->manager,
                new PdoProductTranslationsGateway($this->db)
            ),
            new MarketplaceGateway($this->manager),
            new PdoProductTranslationsGateway($this->db),
            new DefaultCategoryResolver(
                $this->manager,
                $this->manager->getRepository(RemoteCategory::class),
                $this->manager->getRepository(ProductToRemoteCategory::class),
                $this->manager->getRepository(Category::class)
            ),
            new PDO($this->db->getConnection()),
            Shopware()->Container()->get('events'),
            Shopware()->Container()->get('CategoryDenormalization')
        );
    }

    /**
     * Test duplicate ordernumber for regular variant.
     * It must be removed and new article detail will be inserted.
     */
    public function test_duplicate_ordernumber()
    {
        $variants = $this->getVariants();

        $this->assertNotEquals($variants[2]->sku, $variants[1]->sku);

        $variants[2]->sku = $variants[1]->sku;

        foreach ($variants as $variant) {
            $variant->groupId = 'donum';
            $this->productToShop->insertOrUpdate($variant);
        }

        $connectItems = $this->manager->getConnection()->fetchAll(
            'SELECT article_id, article_detail_id, source_id FROM s_plugin_connect_items WHERE source_id = ? AND shop_id = ?',
            [$variants[2]->sourceId, $variants[2]->shopId]
        );
        $this->assertCount(1, $connectItems);

        $detailsCount = $this->manager->getConnection()->fetchColumn(
            'SELECT COUNT(id) FROM s_articles_details WHERE articleID = ?',
            [$connectItems[0]['article_id']]
        );
        $this->assertEquals(3, $detailsCount);

        $detailNumbers = $this->db->fetchCol(
            'SELECT ordernumber FROM s_articles_details WHERE articleID = ?',
            [$connectItems[0]['article_id']]
        );
        $this->assertCount(3, $detailNumbers);
        $expectedNumbers = [
            sprintf('SC-%s-%s', $variants[0]->shopId, $variants[0]->sku),
            sprintf('SC-%s-%s', $variants[2]->shopId, $variants[2]->sku),
            sprintf('SC-%s-%s', $variants[3]->shopId, $variants[3]->sku),
        ];
        $this->assertSame($expectedNumbers, $detailNumbers);

        $expectedSourceIds = [
            $variants[0]->sourceId,
            $variants[2]->sourceId,
            $variants[3]->sourceId,
        ];

        $detailSourceIds = $this->db->fetchCol(
            'SELECT source_id FROM s_plugin_connect_items WHERE article_id = ?',
            [$connectItems[0]['article_id']]
        );
        $this->assertSame($expectedSourceIds, $detailSourceIds);
    }

    /**
     * Test main variant has duplicated ordernumber.
     * Remove main variant and select new one.
     */
    public function test_duplicate_ordernumber_with_main_variant()
    {
        $variants = $this->getVariants();
        $this->assertNotEquals($variants[1]->sku, $variants[0]->sku);

        $variants[3]->sku = $variants[0]->sku;

        foreach ($variants as $variant) {
            $this->productToShop->insertOrUpdate($variant);
        }

        $connectItems = $this->manager->getConnection()->fetchAll(
            'SELECT article_id, article_detail_id, source_id FROM s_plugin_connect_items WHERE source_id = ? AND shop_id = ?',
            [$variants[3]->sourceId, $variants[3]->shopId]
        );

        $detailsCount = $this->manager->getConnection()->fetchColumn(
            'SELECT COUNT(id) FROM s_articles_details WHERE articleID = ?',
            [$connectItems[0]['article_id']]
        );
        $this->assertEquals(3, $detailsCount);

        $detailNumbers = $this->db->fetchCol(
            'SELECT ordernumber FROM s_articles_details WHERE articleID = ?',
            [$connectItems[0]['article_id']]
        );
        $this->assertCount(3, $detailNumbers);
        $expectedNumbers = [
            sprintf('SC-%s-%s', $variants[1]->shopId, $variants[1]->sku),
            sprintf('SC-%s-%s', $variants[2]->shopId, $variants[2]->sku),
            sprintf('SC-%s-%s', $variants[3]->shopId, $variants[3]->sku),
        ];
        $this->assertSame($expectedNumbers, $detailNumbers);

        $expectedSourceIds = [
            $variants[1]->sourceId,
            $variants[2]->sourceId,
            $variants[3]->sourceId,
        ];

        $detailSourceIds = $this->db->fetchCol(
            'SELECT source_id FROM s_plugin_connect_items WHERE article_id = ?',
            [$connectItems[0]['article_id']]
        );
        $this->assertSame($expectedSourceIds, $detailSourceIds);
    }

    /**
     * Test duplicate order number for produt without variants.
     * First product must be removed, then import new one.
     */
    public function test_duplicate_ordernumber_without_variants()
    {
        $product1 = $this->getProduct();
        $product1->sku = 'abcxyz';
        $this->productToShop->insertOrUpdate($product1);

        $articleId1 = $this->manager->getConnection()->fetchColumn(
            'SELECT article_id FROM s_plugin_connect_items WHERE source_id = ? AND shop_id = ?',
            [$product1->sourceId, $product1->shopId]
        );

        $this->assertGreaterThan(0, $articleId1);

        $product2 = $this->getProduct();
        $product2->sku = 'abcxyz';
        $this->productToShop->insertOrUpdate($product2);

        // Verify that first article has been removed
        $article1 = $this->manager->getConnection()->fetchColumn(
            'SELECT COUNT(id) FROM s_articles WHERE id = ?',
            [$articleId1]
        );
        $this->assertEquals(0, $article1);
        $details1 = $this->manager->getConnection()->fetchColumn(
            'SELECT COUNT(id) FROM s_articles_details WHERE articleID = ?',
            [$articleId1]
        );

        $this->assertEquals(0, $details1);

        // Verify that second article is available in DB
        $articleId2 = $this->manager->getConnection()->fetchColumn(
            'SELECT article_id FROM s_plugin_connect_items WHERE source_id = ? AND shop_id = ?',
            [$product2->sourceId, $product2->shopId]
        );
        $this->assertGreaterThan(0, $articleId2);
        $article2 = $this->manager->getConnection()->fetchColumn(
            'SELECT COUNT(id) FROM s_articles WHERE id = ?',
            [$articleId2]
        );
        $this->assertEquals(1, $article2);
        $details2 = $this->manager->getConnection()->fetchColumn(
            'SELECT COUNT(id) FROM s_articles_details WHERE articleID = ?',
            [$articleId2]
        );
        $this->assertGreaterThan(0, $details2);
    }

    /**
     * Article won't be removed when product has same sku and sourceId.
     */
    public function test_update_product_with_same_sku()
    {
        $product = $this->getProduct();
        $product->sku = 'abcxyz';
        $this->productToShop->insertOrUpdate($product);

        $connectItems = $this->manager->getConnection()->fetchAll(
            'SELECT article_id, article_detail_id FROM s_plugin_connect_items WHERE source_id = ? AND shop_id = ?',
            [$product->sourceId, $product->shopId]
        );

        $expectedArticleId = $connectItems[0]['article_id'];
        $expectedArticleDetailId = $connectItems[0]['article_detail_id'];
        $this->assertCount(1, $connectItems);
        $this->assertGreaterThan(0, $expectedArticleId);
        $this->assertGreaterThan(0, $expectedArticleDetailId);

        $this->productToShop->insertOrUpdate($product);

        $connectItemsNew = $this->manager->getConnection()->fetchAll(
            'SELECT article_id, article_detail_id FROM s_plugin_connect_items WHERE source_id = ? AND shop_id = ?',
            [$product->sourceId, $product->shopId]
        );

        $actualArticleId = $connectItemsNew[0]['article_id'];
        $actualArticleDetailId = $connectItemsNew[0]['article_detail_id'];

        $this->assertCount(1, $connectItems);
        $this->assertEquals($expectedArticleId, $actualArticleId);
        $this->assertEquals($expectedArticleDetailId, $actualArticleDetailId);
    }

    /**
     * Insert 4 remote variants. Then delete them one by one.
     * Main variant must be removed at the end.
     * Then the whole product will be removed.
     */
    public function test_delete_variant_by_variant()
    {
        $variants = $this->getVariants();
        foreach ($variants as $variant) {
            $this->productToShop->insertOrUpdate($variant);
        }
        $articleId = $this->manager->getConnection()->fetchColumn(
            'SELECT article_id FROM s_plugin_connect_items WHERE source_id = ? AND shop_id = ?',
            [$variants[0]->sourceId, $variants[0]->shopId]
        );
        $connectItems = $this->manager->getConnection()->fetchAll(
            'SELECT article_id, article_detail_id, source_id, shop_id FROM s_plugin_connect_items WHERE article_id = ?',
            [$articleId]
        );
        $this->assertCount(4, $connectItems);
        // verify that $variants[0] is main variant
        $this->assertEquals($variants[0]->sourceId, $connectItems[0]['source_id']);
        $kind = $this->manager->getConnection()->fetchColumn(
            'SELECT kind FROM s_articles_details WHERE id = ?',
            [$connectItems[0]['article_detail_id']]
        );
        $this->assertEquals(1, $kind);
        $this->productToShop->delete($variants[3]->shopId, $variants[3]->sourceId);
        $this->productToShop->delete($variants[2]->shopId, $variants[2]->sourceId);
        $this->productToShop->delete($variants[1]->shopId, $variants[1]->sourceId);
        $this->productToShop->delete($variants[0]->shopId, $variants[0]->sourceId);
        $newConnectItems = $this->manager->getConnection()->fetchAll(
            'SELECT article_id, article_detail_id, source_id, shop_id FROM s_plugin_connect_items WHERE article_id = ?',
            [$articleId]
        );
        $this->assertCount(0, $newConnectItems);
        $detailsCount = $this->manager->getConnection()->fetchColumn(
            'SELECT COUNT(id) FROM s_articles_details WHERE articleID = ?',
            [$articleId]
        );
        $this->assertEquals(0, $detailsCount);
        $articleCount = $this->manager->getConnection()->fetchColumn(
            'SELECT COUNT(id) FROM s_articles WHERE id = ?',
            [$articleId]
        );
        $this->assertEquals(0, $articleCount);
    }
}
