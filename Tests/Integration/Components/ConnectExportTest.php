<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Tests\Integration\Components;

use Shopware\Components\Model\ModelManager;
use ShopwarePlugins\Connect\Components\ConfigFactory;
use ShopwarePlugins\Connect\Components\ConnectExport;
use ShopwarePlugins\Connect\Components\ErrorHandler;
use ShopwarePlugins\Connect\Components\Validator\ProductAttributesValidator\ProductsAttributesValidator;
use ShopwarePlugins\Connect\Tests\DatabaseTestCaseTrait;
use Shopware\Models\Category\Category;
use Tests\ShopwarePlugins\Connect\ConnectTestHelper;

class ConnectExportTest extends ConnectTestHelper
{
    use DatabaseTestCaseTrait;

    /**
     * @var ModelManager
     */
    private $manager;

    /**
     * @var ConnectExport
     */
    private $connectExport;

    public function setUp()
    {
        $this->manager = Shopware()->Models();
        $this->connectExport = new ConnectExport(
            $this->getHelper(),
            $this->getSDK(),
            $this->manager,
            new ProductsAttributesValidator(),
            ConfigFactory::getConfigInstance(),
            new ErrorHandler(),
            Shopware()->Container()->get('events')
        );
    }

    public function testMarkProductsInToBeDeletedCategories()
    {
        $this->manager->getConnection()->executeQuery('DELETE FROM `s_articles_categories`');
        $this->manager->getConnection()->executeQuery('DELETE FROM `s_plugin_connect_items`');

        $this->manager->getConnection()->executeQuery('INSERT INTO `s_categories` (`parent`, `path`, `description`) VALUES (3, "|3|", "Test123")');
        $categoryId = $this->manager->getConnection()->lastInsertId();

        $this->manager->getConnection()->executeQuery('INSERT INTO `s_plugin_connect_items` (`article_id`, `exported`, `cron_update`) VALUES (3, 1, NULL)');
        $this->manager->getConnection()->executeQuery('INSERT INTO `s_plugin_connect_items` (`article_id`, `exported`, `cron_update`) VALUES (4, 1, NULL)');

        $this->manager->getConnection()->executeQuery('INSERT INTO `s_articles_categories` (`articleID`, `categoryID`) VALUES (?, ?)',
            [3, $categoryId]
        );
        $this->manager->getConnection()->executeQuery('INSERT INTO `s_articles_categories` (`articleID`, `categoryID`) VALUES (?, ?)',
            [4, $categoryId]
        );

        $category = new Category();
        $category->setId($categoryId);

        $this->connectExport->markProductsInToBeDeletedCategories($category);

        $count = $this->manager->getConnection()->executeQuery('SELECT COUNT(*) FROM `s_plugin_connect_items` WHERE `cron_update` = 1')->fetchColumn();
        $this->assertEquals(2, (int) $count);
    }

    public function testMarkProductsInToBeDeletedCategoriesWithChildCategories()
    {
        $this->manager->getConnection()->executeQuery('DELETE FROM `s_articles_categories`');
        $this->manager->getConnection()->executeQuery('DELETE FROM `s_plugin_connect_items`');

        $this->manager->getConnection()->executeQuery('INSERT INTO `s_categories` (`parent`, `path`, `description`) VALUES (3, "|3|", "Test123")');
        $parentId = $this->manager->getConnection()->lastInsertId();

        $this->manager->getConnection()->executeQuery('INSERT INTO `s_categories` (`parent`, `path`, `description`) VALUES (?, ?, ?)',
            [$parentId, "|3|$parentId|", 'Test123456']);
        $child1Id = $this->manager->getConnection()->lastInsertId();

        $this->manager->getConnection()->executeQuery('INSERT INTO `s_categories` (`parent`, `path`, `description`) VALUES (?, ?, ?)',
            [$parentId, "|3|$parentId|", 'Test12345678']);
        $child2Id = $this->manager->getConnection()->lastInsertId();

        $this->manager->getConnection()->executeQuery('INSERT INTO `s_plugin_connect_items` (`article_id`, `exported`, `cron_update`) VALUES (3, 1, NULL)');
        $this->manager->getConnection()->executeQuery('INSERT INTO `s_plugin_connect_items` (`article_id`, `exported`, `cron_update`) VALUES (4, 1, NULL)');
        $this->manager->getConnection()->executeQuery('INSERT INTO `s_plugin_connect_items` (`article_id`, `exported`, `cron_update`) VALUES (5, 1, NULL)');


        $this->manager->getConnection()->executeQuery('INSERT INTO `s_articles_categories` (`articleID`, `categoryID`) VALUES (?, ?)',
            [3, $child1Id]
        );
        $this->manager->getConnection()->executeQuery('INSERT INTO `s_articles_categories` (`articleID`, `categoryID`) VALUES (?, ?)',
            [4, $child2Id]
        );
        $this->manager->getConnection()->executeQuery('INSERT INTO `s_articles_categories` (`articleID`, `categoryID`) VALUES (?, ?)',
            [5, $parentId]
        );

        $category = new Category();
        $category->setId($parentId);

        $this->connectExport->markProductsInToBeDeletedCategories($category);

        $count = $this->manager->getConnection()->executeQuery('SELECT COUNT(*) FROM `s_plugin_connect_items` WHERE `cron_update` = 1')->fetchColumn();
        $this->assertEquals(3, (int) $count);
    }

    public function testMarkProductsInToBeDeletedCategoriesMarksNothing()
    {
        $this->manager->getConnection()->executeQuery('INSERT INTO `s_categories` (`parent`, `path`, `description`) VALUES (3, "|3|", "Test123")');
        $parentId = $this->manager->getConnection()->lastInsertId();
        $category = new Category();
        $category->setId($parentId);

        $this->connectExport->markProductsInToBeDeletedCategories($category);

        $count = $this->manager->getConnection()->executeQuery('SELECT COUNT(*) FROM `s_plugin_connect_items` WHERE `cron_update` = 1')->fetchColumn();
        $this->assertEquals(0, (int) $count);
    }

    public function testHandleMarkedProducts()
    {
        $this->manager->getConnection()->executeQuery('DELETE FROM `s_plugin_connect_items`');

        $this->manager->getConnection()->executeQuery('INSERT INTO `s_plugin_connect_items` (`article_id`, `exported`, `cron_update`) VALUES (3, 1, NULL)');
        $this->manager->getConnection()->executeQuery('INSERT INTO `s_plugin_connect_items` (`article_id`, `exported`, `cron_update`) VALUES (4, 0, NULL)');
        $this->manager->getConnection()->executeQuery('INSERT INTO `s_plugin_connect_items` (`article_id`, `exported`, `cron_update`) VALUES (5, 1, NULL)');


        $this->connectExport->handleMarkedProducts();

        $count = $this->manager->getConnection()->executeQuery('SELECT COUNT(*) FROM `s_plugin_connect_items` WHERE `cron_update` = 1')->fetchColumn();
        $this->assertEquals(0, (int) $count);
    }
}
