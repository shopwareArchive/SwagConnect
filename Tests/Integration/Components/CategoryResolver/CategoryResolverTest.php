<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Tests\Integration\Components\CategoryResolver;

use Shopware\CustomModels\Connect\ProductToRemoteCategory;
use Shopware\CustomModels\Connect\RemoteCategory;
use ShopwarePlugins\Connect\Components\CategoryResolver\AutoCategoryResolver;
use ShopwarePlugins\Connect\Components\ConfigFactory;
use ShopwarePlugins\Connect\Tests\DatabaseTestCaseTrait;
use Shopware\Models\Category\Category;

class CategoryResolverTest extends \PHPUnit_Framework_TestCase
{
    use DatabaseTestCaseTrait;

    private $manager;
    private $config;
    private $categoryRepo;
    private $categoryResolver;

    public function setUp()
    {
        $this->manager = Shopware()->Models();
        $this->config = ConfigFactory::getConfigInstance();
        $this->categoryRepo = $this->manager->getRepository(Category::class);

        $this->categoryResolver = new AutoCategoryResolver(
            $this->manager,
            $this->categoryRepo,
            $this->manager->getRepository(RemoteCategory::class),
            $this->config,
            $this->manager->getRepository(ProductToRemoteCategory::class)
        );
    }

    public function testStoreRemoteCategories()
    {
        $this->manager->getConnection()->executeQuery('DELETE FROM s_plugin_connect_categories');
        $this->manager->getConnection()->executeQuery('DELETE FROM s_plugin_connect_product_to_categories');

        $this->manager->getConnection()->executeQuery('INSERT INTO s_plugin_connect_categories (category_key, label, shop_id) VALUES ("/deutsch", "Deutsch", 1234)');
        $this->manager->getConnection()->executeQuery('INSERT INTO s_plugin_connect_categories (category_key, label, shop_id) VALUES ("/deutsch/test1", "Test 1", 1234)');
        $this->manager->getConnection()->executeQuery('INSERT INTO s_plugin_connect_product_to_categories (connect_category_id, articleID) VALUES (LAST_INSERT_ID(), 3)');
        $this->manager->getConnection()->executeQuery('INSERT INTO s_plugin_connect_categories (category_key, label, shop_id) VALUES ("/deutsch/test2", "Test 2", 1234)');
        $this->manager->getConnection()->executeQuery('INSERT INTO s_plugin_connect_product_to_categories (connect_category_id, articleID) VALUES (LAST_INSERT_ID(), 3)');

        $germanId = $this->manager->getConnection()->executeQuery('SELECT id FROM s_plugin_connect_categories WHERE category_key = "/deutsch" AND shop_id = 1234')->fetchColumn();
        $test1Id = $this->manager->getConnection()->executeQuery('SELECT id FROM s_plugin_connect_categories WHERE category_key = "/deutsch/test1" AND shop_id = 1234')->fetchColumn();
        $test2Id = $this->manager->getConnection()->executeQuery('SELECT id FROM s_plugin_connect_categories WHERE category_key = "/deutsch/test2" AND shop_id = 1234')->fetchColumn();

        $categories = [
            '/deutsch' => 'Deutsch',
            '/deutsch/test1' => 'Test 1',
            '/deutsch/test3' => 'Test 3',
            '/deutsch/test3/test31' => 'Test 3.1'
        ];

        $this->categoryResolver->storeRemoteCategories($categories, 3, 1234);

        $actualGermanId = $this->manager->getConnection()->executeQuery('SELECT id FROM s_plugin_connect_categories WHERE category_key = "/deutsch" AND shop_id = 1234')->fetchColumn();
        $actualTest1Id = $this->manager->getConnection()->executeQuery('SELECT id FROM s_plugin_connect_categories WHERE category_key = "/deutsch/test1" AND shop_id = 1234')->fetchColumn();
        $actualTest2Id = $this->manager->getConnection()->executeQuery('SELECT id FROM s_plugin_connect_categories WHERE category_key = "/deutsch/test2" AND shop_id = 1234')->fetchColumn();

        //Assert that old remote categories aren't changed
        $this->assertEquals($germanId, $actualGermanId);
        $this->assertEquals($test1Id, $actualTest1Id);
        $this->assertEquals($test2Id, $actualTest2Id);

        $test3Id = $this->manager->getConnection()->executeQuery('SELECT id FROM s_plugin_connect_categories WHERE category_key = "/deutsch/test3" AND shop_id = 1234')->fetchColumn();
        $test31Id = $this->manager->getConnection()->executeQuery('SELECT id FROM s_plugin_connect_categories WHERE category_key = "/deutsch/test3/test31" AND shop_id = 1234')->fetchColumn();

        //Assert that new Categories are created
        $this->assertGreaterThan(0, $test3Id);
        $this->assertGreaterThan(0, $test31Id);

        $productToCategoryId = $this->manager->getConnection()->executeQuery('SELECT id FROM s_plugin_connect_product_to_categories WHERE articleID = ? AND connect_category_id = ?',
            [3, $test1Id])->fetchColumn();

        //Assert that old category is still assigned
        $this->assertGreaterThan(0, $productToCategoryId);

        $productToCategoryId = $this->manager->getConnection()->executeQuery('SELECT id FROM s_plugin_connect_product_to_categories WHERE articleID = ? AND connect_category_id = ?',
            [3, $test2Id])->fetchColumn();

        //Assert that removed category is not assigned
        $this->assertEquals(false, $productToCategoryId);

        $productToCategoryId = $this->manager->getConnection()->executeQuery('SELECT id FROM s_plugin_connect_product_to_categories WHERE articleID = ? AND connect_category_id = ?',
            [3, $test3Id])->fetchColumn();

        //Assert that new, not-leaf category is assigned
        //This is necessary that ext.js find all products in not-leaf categories in import window
        $this->assertGreaterThan(0, $productToCategoryId);

        $productToCategoryId = $this->manager->getConnection()->executeQuery('SELECT id FROM s_plugin_connect_product_to_categories WHERE articleID = ? AND connect_category_id = ?',
            [3, $test31Id])->fetchColumn();

        //Assert that new, leaf category is assigned
        $this->assertGreaterThan(0, $productToCategoryId);
    }
}
