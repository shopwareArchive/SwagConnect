<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Tests\Integration\Components\CategoryResolver;

use Shopware\CustomModels\Connect\ProductToRemoteCategory;
use Shopware\CustomModels\Connect\RemoteCategory;
use ShopwarePlugins\Connect\Components\CategoryResolver;
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
    /** @var CategoryResolver */
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
            Shopware()->Container()->get('CategoryDenormalization'),
            $this->manager->getRepository(ProductToRemoteCategory::class)
        );
    }

    public function testCreateLocalCategory()
    {
        $this->manager->getConnection()->executeQuery('DELETE FROM s_plugin_connect_categories');
        $this->manager->getConnection()->executeQuery('DELETE FROM s_plugin_connect_product_to_categories');

        $this->manager->getConnection()->executeQuery('INSERT INTO s_plugin_connect_categories (category_key, label, shop_id) VALUES ("/deutsch", "Deutsch", 1234)');
        $this->manager->getConnection()->executeQuery('INSERT INTO s_plugin_connect_categories (category_key, label, shop_id) VALUES ("/deutsch/test1", "Test 1", 1234)');

        $this->categoryResolver->createLocalCategory('Test 1', '/deutsch/test1', 3, 1234);

        $row = $this->manager->getConnection()->fetchAll('SELECT * FROM s_categories WHERE description = "Test 1" AND parent = 3');

        $this->assertNotEmpty($row);
        $now = new \DateTime('now');
        $added = new \DateTime($row[0]['added']);
        $changed = new \DateTime($row[0]['changed']);
        // assert that timestamps are not older than 5 seconds
        $this->assertEquals($now->getTimestamp(), $added->getTimestamp(), '', 5);
        $this->assertEquals($now->getTimestamp(), $changed->getTimestamp(), '', 5);
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

        $germanId = $this->manager->getConnection()->executeQuery('SELECT id FROM s_plugin_connect_categories WHERE category_key = "/deutsch"')->fetchColumn();
        $test1Id = $this->manager->getConnection()->executeQuery('SELECT id FROM s_plugin_connect_categories WHERE category_key = "/deutsch/test1"')->fetchColumn();
        $test2Id = $this->manager->getConnection()->executeQuery('SELECT id FROM s_plugin_connect_categories WHERE category_key = "/deutsch/test2"')->fetchColumn();

        $categories = [
            '/deutsch' => 'Deutsch',
            '/deutsch/test1' => 'Test 1',
            '/deutsch/test3' => 'Test 3',
            '/deutsch/test3/test31' => 'Test 3.1'
        ];

        $this->categoryResolver->storeRemoteCategories($categories, 3, 1234);

        $actualGermanId = $this->manager->getConnection()->executeQuery('SELECT id FROM s_plugin_connect_categories WHERE category_key = "/deutsch"')->fetchColumn();
        $actualTest1Id = $this->manager->getConnection()->executeQuery('SELECT id FROM s_plugin_connect_categories WHERE category_key = "/deutsch/test1"')->fetchColumn();
        $actualTest2Id = $this->manager->getConnection()->executeQuery('SELECT id FROM s_plugin_connect_categories WHERE category_key = "/deutsch/test2"')->fetchColumn();

        //Assert that old remote categories aren't changed
        $this->assertEquals($germanId, $actualGermanId);
        $this->assertEquals($test1Id, $actualTest1Id);
        $this->assertEquals($test2Id, $actualTest2Id);

        $test3Id = $this->manager->getConnection()->executeQuery('SELECT id FROM s_plugin_connect_categories WHERE category_key = "/deutsch/test3"')->fetchColumn();
        $test31Id = $this->manager->getConnection()->executeQuery('SELECT id FROM s_plugin_connect_categories WHERE category_key = "/deutsch/test3/test31"')->fetchColumn();

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

    public function testStoreRemoteCategoriesDeletesLocalArticleAssignment()
    {
        $this->manager->getConnection()->executeQuery('DELETE FROM s_plugin_connect_categories');
        $this->manager->getConnection()->executeQuery('DELETE FROM s_plugin_connect_product_to_categories');

        $this->manager->getConnection()->executeQuery('INSERT IGNORE INTO `s_categories` (`parent`, `path`, `description`) VALUES (?, ?, ?)',
            ['3', '|3|', 'TestCategory']
        );
        $localCategoryId = $this->manager->getConnection()->lastInsertId();
        $this->manager->getConnection()->executeQuery('INSERT IGNORE INTO `s_categories_attributes` (`categoryID`, `connect_imported_category`) VALUES (?, ?)',
            [$localCategoryId, 1]
        );

        $this->manager->getConnection()->executeQuery('INSERT INTO s_plugin_connect_categories (category_key, label, shop_id) VALUES ("/deutsch", "Deutsch", 1234)');
        $this->manager->getConnection()->executeQuery('INSERT INTO s_plugin_connect_categories (category_key, label, shop_id) VALUES ("/deutsch/test2", "Test 2", 1234)');
        $this->manager->getConnection()->executeQuery('INSERT INTO s_plugin_connect_product_to_categories (connect_category_id, articleID) VALUES (LAST_INSERT_ID(), 3)');

        $connectCategoryId = $this->manager->getConnection()->executeQuery('SELECT id FROM s_plugin_connect_categories WHERE category_key = "/deutsch/test2"')->fetchColumn();
      
        $this->manager->getConnection()->executeQuery('INSERT INTO s_plugin_connect_categories_to_local_categories (remote_category_id, local_category_id) VALUES (?, ?)',
            [$connectCategoryId, $localCategoryId]);

        $this->manager->getConnection()->executeQuery('INSERT INTO s_articles_categories (articleID, categoryID) VALUES (?, ?)',
            [3, $localCategoryId]);

        $categories = [
            '/deutsch' => 'Deutsch',
            '/deutsch/test1' => 'Test 1',
            '/deutsch/test3' => 'Test 3',
            '/deutsch/test3/test31' => 'Test 3.1'
        ];

        $this->categoryResolver->storeRemoteCategories($categories, 3, 1234);

        $productToCategoryId = $this->manager->getConnection()->fetchColumn('SELECT id FROM s_plugin_connect_product_to_categories WHERE articleID = ? AND connect_category_id = ?',
            [3, $connectCategoryId]
        );
        //Assert that removed category is not assigned
        $this->assertFalse($productToCategoryId);

        $categoryAssignment = $this->manager->getConnection()->fetchColumn('SELECT * FROM `s_articles_categories` WHERE articleID = ? AND categoryID = ?',
            [3, $localCategoryId]
        );
        $this->assertFalse($categoryAssignment);

        $localCategory = $this->manager->getConnection()->fetchColumn('SELECT * FROM `s_categories` WHERE id = ?',
            [$localCategoryId]
        );
        $this->assertFalse($localCategory);
    }

    public function testDeleteEmptyConnectCategoriesDeletesMultiple()
    {
        $this->manager->getConnection()->executeQuery('INSERT IGNORE INTO `s_categories` (`parent`, `path`, `description`) VALUES (?, ?, ?)',
            ['3', '|3|', 'TestCategory']
        );
        $firstCategoryId = $this->manager->getConnection()->lastInsertId();
        $this->manager->getConnection()->executeQuery('INSERT IGNORE INTO `s_categories_attributes` (`categoryID`, `connect_imported_category`) VALUES (?, ?)',
            [$firstCategoryId, 1]
        );

        $this->manager->getConnection()->executeQuery('INSERT IGNORE INTO `s_categories` (`parent`, `path`, `description`) VALUES (?, ?, ?)',
            ['3', '|3|', 'TestCategory']
        );
        $secondCategoryId = $this->manager->getConnection()->lastInsertId();
        $this->manager->getConnection()->executeQuery('INSERT IGNORE INTO `s_categories_attributes` (`categoryID`, `connect_imported_category`) VALUES (?, ?)',
            [$secondCategoryId, 1]
        );

        $this->categoryResolver->deleteEmptyConnectCategories([$firstCategoryId, $secondCategoryId]);

        $firstCategory = $this->manager->getConnection()->fetchColumn('SELECT * FROM `s_categories` WHERE id = ?',
            [$firstCategoryId]
        );
        $this->assertFalse($firstCategory);

        $secondCategory = $this->manager->getConnection()->fetchColumn('SELECT * FROM `s_categories` WHERE id = ?',
            [$secondCategoryId]
        );
        $this->assertFalse($secondCategory);
    }

    public function testDeleteEmptyConnectCategoriesDeletesParent()
    {
        $this->manager->getConnection()->executeQuery('INSERT IGNORE INTO `s_categories` (`parent`, `path`, `description`) VALUES (?, ?, ?)',
            ['3', '|3|', 'TestCategory']
        );
        $firstCategoryId = $this->manager->getConnection()->lastInsertId();
        $this->manager->getConnection()->executeQuery('INSERT IGNORE INTO `s_categories_attributes` (`categoryID`, `connect_imported_category`) VALUES (?, ?)',
            [$firstCategoryId, 1]
        );
        $this->manager->getConnection()->executeQuery('INSERT IGNORE INTO `s_categories` (`parent`, `path`, `description`) VALUES (?, ?, ?)',
            [$firstCategoryId, "|3|$firstCategoryId|", 'TestCategory']
        );
        $secondCategoryId = $this->manager->getConnection()->lastInsertId();
        $this->manager->getConnection()->executeQuery('INSERT IGNORE INTO `s_categories_attributes` (`categoryID`, `connect_imported_category`) VALUES (?, ?)',
            [$secondCategoryId, 1]
        );

        $this->categoryResolver->deleteEmptyConnectCategories([$secondCategoryId]);

        $firstCategory = $this->manager->getConnection()->fetchColumn('SELECT * FROM `s_categories` WHERE id = ?',
            [$firstCategoryId]
        );
        $this->assertFalse($firstCategory);

        $secondCategory = $this->manager->getConnection()->fetchColumn('SELECT * FROM `s_categories` WHERE id = ?',
            [$secondCategoryId]
        );
        $this->assertFalse($secondCategory);
    }

    public function testDeleteEmptyConnectCategoriesDontDeletesNotConnectCategory()
    {
        $this->manager->getConnection()->executeQuery('INSERT IGNORE INTO `s_categories` (`parent`, `path`, `description`) VALUES (?, ?, ?)',
            ['3', '|3|', 'TestCategory']
        );
        $notConnectCategoryId = $this->manager->getConnection()->lastInsertId();

        $this->categoryResolver->deleteEmptyConnectCategories([$notConnectCategoryId]);

        $notConnectCategory = $this->manager->getConnection()->fetchColumn('SELECT COUNT(*) FROM `s_categories` WHERE id = ?',
            [$notConnectCategoryId]
        );
        $this->assertEquals(1, $notConnectCategory);
    }

    public function testDeleteEmptyConnectCategoriesDontDeletesNotEmptyCategory()
    {
        $this->manager->getConnection()->executeQuery('INSERT IGNORE INTO `s_categories` (`parent`, `path`, `description`) VALUES (?, ?, ?)',
            ['3', '|3|', 'TestCategory']
        );
        $notEmptyCategoryId = $this->manager->getConnection()->lastInsertId();
        $this->manager->getConnection()->executeQuery('INSERT IGNORE INTO `s_categories_attributes` (`categoryID`, `connect_imported_category`) VALUES (?, ?)',
            [$notEmptyCategoryId, 1]
        );
        $this->manager->getConnection()->executeQuery('INSERT IGNORE INTO `s_articles_categories` (`articleID`, `categoryID`) VALUES (?, ?)',
            [1, $notEmptyCategoryId]
        );

        $this->categoryResolver->deleteEmptyConnectCategories([$notEmptyCategoryId]);

        $notEmptyCategory = $this->manager->getConnection()->fetchColumn('SELECT COUNT(*) FROM `s_categories` WHERE id = ?',
            [$notEmptyCategoryId]
        );
        $this->assertEquals(1, $notEmptyCategory);
    }

    public function testDeleteEmptyConnectCategoriesDontDeletesParentWithSiblings()
    {
        $this->manager->getConnection()->executeQuery('INSERT IGNORE INTO `s_categories` (`parent`, `path`, `description`) VALUES (?, ?, ?)',
            ['3', '|3|', 'TestCategory']
        );
        $firstCategoryId = $this->manager->getConnection()->lastInsertId();
        $this->manager->getConnection()->executeQuery('INSERT IGNORE INTO `s_categories_attributes` (`categoryID`, `connect_imported_category`) VALUES (?, ?)',
            [$firstCategoryId, 1]
        );

        $this->manager->getConnection()->executeQuery('INSERT IGNORE INTO `s_categories` (`parent`, `path`, `description`) VALUES (?, ?, ?)',
            [$firstCategoryId, "|3|$firstCategoryId|", 'TestCategory']
        );
        $secondCategoryId = $this->manager->getConnection()->lastInsertId();
        $this->manager->getConnection()->executeQuery('INSERT IGNORE INTO `s_categories_attributes` (`categoryID`, `connect_imported_category`) VALUES (?, ?)',
            [$secondCategoryId, 1]
        );

        $this->manager->getConnection()->executeQuery('INSERT IGNORE INTO `s_categories` (`parent`, `path`, `description`) VALUES (?, ?, ?)',
            [$firstCategoryId, "|3|$firstCategoryId|", 'TestCategory']
        );
        $thirdCategoryId = $this->manager->getConnection()->lastInsertId();

        $this->categoryResolver->deleteEmptyConnectCategories([$secondCategoryId]);

        $secondCategory = $this->manager->getConnection()->fetchColumn('SELECT * FROM `s_categories` WHERE id = ?',
            [$secondCategoryId]
        );
        $this->assertFalse($secondCategory);

        $firstCategory = $this->manager->getConnection()->fetchColumn('SELECT COUNT(*) FROM `s_categories` WHERE id = ?',
            [$firstCategoryId]
        );
        $this->assertEquals(1, $firstCategory);

        $thirdCategory = $this->manager->getConnection()->fetchColumn('SELECT COUNT(*) FROM `s_categories` WHERE id = ?',
            [$thirdCategoryId]
        );
        $this->assertEquals(1, $thirdCategory);
    }
}
