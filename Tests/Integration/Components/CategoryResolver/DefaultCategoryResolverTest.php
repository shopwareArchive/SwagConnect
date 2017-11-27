<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Tests\Integration\Components\CategoryResolver;

use Shopware\CustomModels\Connect\ProductToRemoteCategory;
use Shopware\CustomModels\Connect\RemoteCategory;
use ShopwarePlugins\Connect\Components\CategoryResolver\DefaultCategoryResolver;
use ShopwarePlugins\Connect\Tests\DatabaseTestCaseTrait;
use Shopware\Models\Category\Category;

class DefaultCategoryResolverTest extends \PHPUnit_Framework_TestCase
{
    use DatabaseTestCaseTrait;

    private $manager;
    private $categoryRepo;
    /** @var DefaultCategoryResolver */
    private $categoryResolver;

    public function setUp()
    {
        $this->manager = Shopware()->Models();
        $this->categoryRepo = $this->manager->getRepository(Category::class);

        $this->categoryResolver = new DefaultCategoryResolver(
            $this->manager,
            $this->manager->getRepository(RemoteCategory::class),
            $this->manager->getRepository(ProductToRemoteCategory::class),
            $this->categoryRepo,
            Shopware()->Container()->get('CategoryDenormalization')
        );
    }

    public function testResolveReturnsAll()
    {
        $this->manager->getConnection()->executeQuery('DELETE FROM s_categories');
        $this->manager->getConnection()->executeQuery('DELETE FROM s_plugin_connect_categories');
        $this->manager->getConnection()->executeQuery('DELETE FROM s_plugin_connect_categories_to_local_categories');
        $this->importFixtures(__DIR__ . '/../_fixtures/categories.sql');
        $categories = [
            '/deutsch' => 'Deutsch',
            '/deutsch/test1' => 'Test 1',
            '/deutsch/test2' => 'Test 2'
        ];

        $localCategories = $this->categoryResolver->resolve($categories, 1234, 'Teststream');

        $this->assertEquals([2222, 3333, 4444], $localCategories);
    }

    public function testResolveReturnsNotAll()
    {
        $this->manager->getConnection()->executeQuery('DELETE FROM s_categories');
        $this->manager->getConnection()->executeQuery('DELETE FROM s_plugin_connect_categories');
        $this->manager->getConnection()->executeQuery('DELETE FROM s_plugin_connect_categories_to_local_categories');
        $this->importFixtures(__DIR__ . '/../_fixtures/categories.sql');
        $categories = [
            '/deutsch' => 'Deutsch',
            '/deutsch/test1' => 'Test 1',
            '/deutsch/test2' => 'Test 2'
        ];

        $localCategories = $this->categoryResolver->resolve($categories, 1234, 'AwesomeTest');

        $this->assertEquals([2222, 3333], $localCategories);
    }
}
