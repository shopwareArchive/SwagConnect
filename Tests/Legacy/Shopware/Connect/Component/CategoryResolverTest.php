<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\ShopwarePlugins\Connect\Component;

use Shopware\CustomModels\Connect\ProductToRemoteCategory;
use ShopwarePlugins\Connect\Components\CategoryResolver\AutoCategoryResolver;
use ShopwarePlugins\Connect\Components\ConfigFactory;
use Tests\ShopwarePlugins\Connect\ConnectTestHelper;

class CategoryResolverTest extends ConnectTestHelper
{
    /** @var \ShopwarePlugins\Connect\Components\CategoryResolver\AutoCategoryResolver */
    private $categoryResolver;
    private $manager;
    private $categoryRepo;

    /** @var \ShopwarePlugins\Connect\Components\Config */
    private $config;

    public function setUp()
    {
        parent::setUp();

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

        $this->manager->getConnection()->beginTransaction();
    }

    public function tearDown()
    {
        $this->manager->getConnection()->rollBack();
    }

    public function testCreateLocalCategory()
    {
        $this->manager->getConnection()->executeQuery('INSERT IGNORE INTO `s_plugin_connect_categories` (`category_key`, `label`) 
              VALUES (?, ?)',
            ['/deutsch/test12345', 'Test12345']);

        $this->categoryResolver->createLocalCategory(['name' => 'Test12345', 'categoryId' => '/deutsch/test12345'], 3);

        $parentId = $this->manager->getConnection()->fetchColumn('SELECT `parent` FROM `s_categories` WHERE `description` = "Test12345"');
        $this->assertEquals('3', $parentId);
    }
}
