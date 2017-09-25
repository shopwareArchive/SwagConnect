<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Tests\Integration\Components;

use Shopware\CustomModels\Connect\ProductToRemoteCategory;
use ShopwarePlugins\Connect\Components\CategoryResolver\AutoCategoryResolver;
use ShopwarePlugins\Connect\Components\ConfigFactory;
use ShopwarePlugins\Connect\Tests\DatabaseTestCaseTrait;

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
        $this->categoryRepo = $this->manager->getRepository('Shopware\Models\Category\Category');

        $this->categoryResolver = new AutoCategoryResolver(
            $this->manager,
            $this->categoryRepo,
            $this->manager->getRepository('Shopware\CustomModels\Connect\RemoteCategory'),
            $this->config,
            $this->manager->getRepository(ProductToRemoteCategory::class)
        );
    }

    public function testStoreRemoteCategories()
    {
        $this->manager->getConnection()->executeQuery('DELETE FROM s_plugin_connect_categories');
        $this->manager->getConnection()->executeQuery('DELETE FROM s_plugin_connect_product_to_categories');

        $this->manager->getConnection()->executeQuery('INSERT INTO s_plugin_connect_categories (category_key, label) VALUES ("/deutsch", "Deutsch")');
        $this->manager->getConnection()->executeQuery('INSERT INTO s_plugin_connect_categories (category_key, label) VALUES ("/deutsch/test1", "Test 1")');
        $this->manager->getConnection()->executeQuery('INSERT INTO s_plugin_connect_product_to_categories (connect_category_id, articleID) VALUES (LAST_INSERT_ID(), 3)');
        $this->manager->getConnection()->executeQuery('INSERT INTO s_plugin_connect_categories (category_key, label) VALUES ("/deutsch/test2", "Test 2")');
        $this->manager->getConnection()->executeQuery('INSERT INTO s_plugin_connect_product_to_categories (connect_category_id, articleID) VALUES (LAST_INSERT_ID(), 3)');

        $germanID = $this->manager->getConnection()->executeQuery('SELECT id FROM s_plugin_connect_categories WHERE category_key = "/deutsch"')->fetchColumn();
        $test1ID = $this->manager->getConnection()->executeQuery('SELECT id FROM s_plugin_connect_categories WHERE category_key = "/deutsch/test1"')->fetchColumn();
        $test2ID = $this->manager->getConnection()->executeQuery('SELECT id FROM s_plugin_connect_categories WHERE category_key = "/deutsch/test2"')->fetchColumn();


        $categories = [
            '/deutsch' => 'Deutsch',
            '/deutsch/test1' => 'Test 1',
            '/deutsch/test3' => 'Test 3',
            '/deutsch/test3/test31' => 'Test 3.1'
        ];

        $this->categoryResolver->storeRemoteCategories($categories, 3);

        $actualGermanID = $this->manager->getConnection()->executeQuery('SELECT id FROM s_plugin_connect_categories WHERE category_key = "/deutsch"')->fetchColumn();
        $actualTest1ID = $this->manager->getConnection()->executeQuery('SELECT id FROM s_plugin_connect_categories WHERE category_key = "/deutsch/test1"')->fetchColumn();
        $actualTest2ID = $this->manager->getConnection()->executeQuery('SELECT id FROM s_plugin_connect_categories WHERE category_key = "/deutsch/test2"')->fetchColumn();

        //Assert that old remote categories aren't changed
        $this->assertEquals($germanID, $actualGermanID);
        $this->assertEquals($test1ID, $actualTest1ID);
        $this->assertEquals($test2ID, $actualTest2ID);

        $test3ID = $this->manager->getConnection()->executeQuery('SELECT id FROM s_plugin_connect_categories WHERE category_key = "/deutsch/test3"')->fetchColumn();
        $test31ID = $this->manager->getConnection()->executeQuery('SELECT id FROM s_plugin_connect_categories WHERE category_key = "/deutsch/test3/test31"')->fetchColumn();

        //Assert that new Categories are created
        $this->assertInternalType('string', $test3ID);
        $this->assertInternalType('string', $test31ID);

        $productToCategoryID = $this->manager->getConnection()->executeQuery("SELECT id FROM s_plugin_connect_product_to_categories WHERE articleID = 3 AND connect_category_id = $test1ID")->fetchColumn();

        //Assert that old category is still assigned
        $this->assertInternalType('string', $productToCategoryID);

        $productToCategoryID = $this->manager->getConnection()->executeQuery("SELECT id FROM s_plugin_connect_product_to_categories WHERE articleID = 3 AND connect_category_id = $test2ID")->fetchColumn();

        //Assert that removed category is not assigned
        $this->assertEquals(false, $productToCategoryID);

        $productToCategoryID = $this->manager->getConnection()->executeQuery("SELECT id FROM s_plugin_connect_product_to_categories WHERE articleID = 3 AND connect_category_id = $test3ID")->fetchColumn();

        //Assert that new, not-leaf category is assigned
        //This is necessary that ext.js find all products in not-leaf categories in import window
        $this->assertInternalType('string', $productToCategoryID);

        $productToCategoryID = $this->manager->getConnection()->executeQuery("SELECT id FROM s_plugin_connect_product_to_categories WHERE articleID = 3 AND connect_category_id = $test31ID")->fetchColumn();

        //Assert that new, leaf category is assigned
        $this->assertInternalType('string', $productToCategoryID);
    }
}
