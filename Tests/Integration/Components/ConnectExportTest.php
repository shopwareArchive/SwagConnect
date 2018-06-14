<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Tests\Integration\Components;

use Shopware\Components\Model\ModelManager;
use Shopware\CustomModels\Connect\Attribute;
use ShopwarePlugins\Connect\Components\ConfigFactory;
use ShopwarePlugins\Connect\Components\ConnectExport;
use ShopwarePlugins\Connect\Components\ErrorHandler;
use ShopwarePlugins\Connect\Components\Validator\ProductAttributesValidator\ProductsAttributesValidator;
use ShopwarePlugins\Connect\Struct\SearchCriteria;
use ShopwarePlugins\Connect\Tests\ConnectTestHelperTrait;
use ShopwarePlugins\Connect\Tests\DatabaseTestCaseTrait;
use Shopware\Models\Category\Category;

class ConnectExportTest extends \PHPUnit_Framework_TestCase
{
    use DatabaseTestCaseTrait;
    use ConnectTestHelperTrait;

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

    public function testMarkProductsInToBeDeletedCategoriesWithChildCategories()
    {
        $this->importFixtures(__DIR__ . '/../_fixtures/articles_connect_items_categories.sql');
        $category = new Category();
        $category->setId(1884);

        $this->connectExport->markProductsInToBeDeletedCategories($category);

        $count = $this->manager->getConnection()->executeQuery('SELECT COUNT(*) FROM `s_plugin_connect_items` WHERE `cron_update` = 1')->fetchColumn();
        $this->assertEquals(3, (int) $count);
    }

    public function testMarkProductsInToBeDeletedCategoriesMarksNothing()
    {
        $this->importFixtures(__DIR__ . '/../_fixtures/articles_connect_items_categories.sql');
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
        $this->importFixtures(__DIR__ . '/../_fixtures/articles_connect_items_categories.sql');
        $this->manager->getConnection()->executeQuery('DELETE FROM `s_plugin_connect_items`');

        $this->manager->getConnection()->executeQuery('INSERT INTO `s_plugin_connect_items` (`article_id`, `exported`, `cron_update`) VALUES (3, 1, NULL)');
        $this->manager->getConnection()->executeQuery('INSERT INTO `s_plugin_connect_items` (`article_id`, `exported`, `cron_update`) VALUES (4, 0, NULL)');
        $this->manager->getConnection()->executeQuery('INSERT INTO `s_plugin_connect_items` (`article_id`, `exported`, `cron_update`) VALUES (5, 1, NULL)');

        $this->connectExport->handleMarkedProducts();

        $count = $this->manager->getConnection()->executeQuery('SELECT COUNT(*) FROM `s_plugin_connect_items` WHERE `cron_update` = 1')->fetchColumn();
        $this->assertEquals(0, (int) $count);
    }

    public function testGetExportList()
    {
        $this->manager->getConnection()->executeQuery('DELETE FROM `s_plugin_connect_items`');
        $this->importFixtures(__DIR__ . '/../_fixtures/simple_connect_items.sql');

        $criteria = new SearchCriteria();
        $criteria->limit = 20;
        $criteria->offset = 0;

        $result = $this->connectExport->getExportList($criteria);

        $this->assertCount(5, $result->articles);
        $this->assertEquals(2, $result->articles[0]['id']);
        $this->assertEquals(89, $result->articles[4]['id']);
        $this->assertEquals(5, $result->count);
    }

    public function testProcessChanges()
    {
        $this->manager->getConnection()->executeQuery('DELETE FROM `s_plugin_connect_items`');
        $this->importFixtures(__DIR__ . '/../_fixtures/simple_connect_items.sql');

        $this->manager->getConnection()->executeQuery('DELETE FROM s_articles_details WHERE id = 125');

        $this->connectExport->processChanged(['2-125', '2-124']);

        $status = $this->manager->getConnection()->fetchColumn('SELECT export_status FROM s_plugin_connect_items WHERE source_id = "2-125"');
        $this->assertEquals(Attribute::STATUS_DELETE, $status);

        // No matter that it is error -> it says that ConnectExport->export() is called for this sourceId
        $status = $this->manager->getConnection()->fetchColumn('SELECT export_status FROM s_plugin_connect_items WHERE source_id = "2-124"');
        $this->assertEquals(Attribute::STATUS_ERROR_PRICE, $status);
    }
}
