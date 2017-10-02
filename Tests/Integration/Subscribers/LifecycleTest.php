<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Tests\Integration\Subscribers;

use ShopwarePlugins\Connect\Components\Config;
use ShopwarePlugins\Connect\Components\ConnectExport;
use ShopwarePlugins\Connect\Components\ErrorHandler;
use ShopwarePlugins\Connect\Components\Validator\ProductAttributesValidator\ProductsAttributesValidator;
use ShopwarePlugins\Connect\Subscribers\Lifecycle;
use ShopwarePlugins\Connect\Tests\ConnectTestHelperTrait;
use ShopwarePlugins\Connect\Tests\DatabaseTestCaseTrait;
use Shopware\Models\Category\Category;

class LifecycleTest extends \PHPUnit_Framework_TestCase
{
    use DatabaseTestCaseTrait;
    use ConnectTestHelperTrait;

    private $manager;
    private $config;

    public function setUp()
    {
        $this->manager = Shopware()->Models();
        $this->config = $this->createMock(Config::class);
    }

    public function testCategoryDeletionAutoUpdate()
    {
        $this->importFixtures(__DIR__ . '/../_fixtures/articles_connect_items_categories.sql');
        $this->config->method('getConfig')
            ->willReturn(Config::UPDATE_AUTO);
        //Creation has to be here because config value for update strategy is set at creation time
        $lifeycle = new Lifecycle(
            $this->manager,
            $this->getHelper(),
            $this->getSDK(),
            $this->config,
            new ConnectExport(
                $this->getHelper(),
                $this->getSDK(),
                $this->manager,
                new ProductsAttributesValidator(),
                $this->config,
                new ErrorHandler(),
                Shopware()->Container()->get('events')
            )
        );
        $category = new Category;
        $category->setId(1884);
        $eventArgs = new \Enlight_Event_EventArgs();
        $eventArgs['entity'] = $category;

        $lifeycle->onPreDeleteCategory($eventArgs);

        $count = $this->manager->getConnection()->executeQuery('SELECT COUNT(*) FROM `s_plugin_connect_items` WHERE `cron_update` = 1')->fetchColumn();
        $this->assertEquals(3, (int) $count);

        $lifeycle->onPostDeleteCategory($eventArgs);

        $count = $this->manager->getConnection()->executeQuery('SELECT COUNT(*) FROM `s_plugin_connect_items` WHERE `cron_update` = 1')->fetchColumn();
        $this->assertEquals(0, (int) $count);
    }

    public function testCategoryDeletionCronUpdate()
    {
        $this->importFixtures(__DIR__ . '/../_fixtures/articles_connect_items_categories.sql');
        $this->config->method('getConfig')
            ->willReturn(Config::UPDATE_CRON_JOB);
        //Creation has to be here because config value for update strategy is set at creation time
        $lifeycle = new Lifecycle(
            $this->manager,
            $this->getHelper(),
            $this->getSDK(),
            $this->config,
            new ConnectExport(
                $this->getHelper(),
                $this->getSDK(),
                $this->manager,
                new ProductsAttributesValidator(),
                $this->config,
                new ErrorHandler(),
                Shopware()->Container()->get('events')
            )
        );
        $category = new Category;
        $category->setId(1884);
        $eventArgs = new \Enlight_Event_EventArgs();
        $eventArgs['entity'] = $category;

        $lifeycle->onPreDeleteCategory($eventArgs);
        $count = $this->manager->getConnection()->executeQuery('SELECT COUNT(*) FROM `s_plugin_connect_items` WHERE `cron_update` = 1')->fetchColumn();
        $this->assertEquals(3, (int) $count);
        $lifeycle->onPostDeleteCategory($eventArgs);

        $count = $this->manager->getConnection()->executeQuery('SELECT COUNT(*) FROM `s_plugin_connect_items` WHERE `cron_update` = 1')->fetchColumn();
        $this->assertEquals(3, (int) $count);
    }

    public function testCategoryDeletionManualUpdate()
    {
        $this->importFixtures(__DIR__ . '/../_fixtures/articles_connect_items_categories.sql');
        $this->config->method('getConfig')
            ->willReturn(Config::UPDATE_MANUAL);
        //Creation has to be here because config value for update strategy is set at creation time
        $lifeycle = new Lifecycle(
            $this->manager,
            $this->getHelper(),
            $this->getSDK(),
            $this->config,
            new ConnectExport(
                $this->getHelper(),
                $this->getSDK(),
                $this->manager,
                new ProductsAttributesValidator(),
                $this->config,
                new ErrorHandler(),
                Shopware()->Container()->get('events')
            )
        );
        $category = new Category;
        $category->setId(1884);
        $eventArgs = new \Enlight_Event_EventArgs();
        $eventArgs['entity'] = $category;

        $lifeycle->onPreDeleteCategory($eventArgs);
        $count = $this->manager->getConnection()->executeQuery('SELECT COUNT(*) FROM `s_plugin_connect_items` WHERE `cron_update` = 1')->fetchColumn();
        $this->assertEquals(0, (int) $count);
        $lifeycle->onPostDeleteCategory($eventArgs);

        $count = $this->manager->getConnection()->executeQuery('SELECT COUNT(*) FROM `s_plugin_connect_items` WHERE `cron_update` = 1')->fetchColumn();
        $this->assertEquals(0, (int) $count);
    }
}
