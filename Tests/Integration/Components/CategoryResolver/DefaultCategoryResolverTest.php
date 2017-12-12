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

    public function testResolveReturnsAllWithAddingAssignmentOfStream()
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

        $this->assertEquals([2222, 3333, 4444], $localCategories);

        $streamAssignment = $this->manager->getConnection()->fetchColumn('SELECT id FROM s_plugin_connect_categories_to_local_categories WHERE local_category_id = 4444 AND remote_category_id = 4444 AND stream = "AwesomeTest"');
        $this->assertNotFalse($streamAssignment);
    }

    public function testResolveCreatesChildCategories()
    {
        $this->manager->getConnection()->executeQuery('DELETE FROM s_categories');
        $this->manager->getConnection()->executeQuery('DELETE FROM s_plugin_connect_categories');
        $this->manager->getConnection()->executeQuery('DELETE FROM s_plugin_connect_categories_to_local_categories');
        $this->importFixtures(__DIR__ . '/../_fixtures/one_connect_to_local_category.sql');
        $categories = [
            '/deutsch' => 'Deutsch',
            '/deutsch/test1' => 'Test 1',
            '/deutsch/test2' => 'Test 2'
        ];

        $localCategories = $this->categoryResolver->resolve($categories, 1234, 'Teststream');

        $this->assertArraySubset([2222], $localCategories);
        $this->assertCount(3, $localCategories);
        $mappedRemoteCategories = [];
        foreach ($localCategories as $localCategory) {
            $categoryAssignment = $this->manager->getConnection()->fetchColumn('
            SELECT pc.id
            FROM s_categories AS sc
            INNER JOIN s_categories_attributes AS sca ON sc.id = sca.categoryID
            INNER JOIN s_plugin_connect_categories_to_local_categories AS pclc ON pclc.local_category_id = sc.id
            INNER JOIN s_plugin_connect_categories AS pc ON pc.id = pclc.remote_category_id
            WHERE sc.id = ? AND sca.connect_imported_category = 1 AND pclc.stream = "Teststream" AND pc.id IN (2222, 3333, 4444)', [$localCategory]);
            $this->assertNotFalse($categoryAssignment);
            $mappedRemoteCategories[] = $categoryAssignment;

            $streamAssignment = $this->manager->getConnection()->fetchColumn('SELECT id FROM s_plugin_connect_categories_to_local_categories WHERE local_category_id = ? AND remote_category_id IN (2222, 3333, 4444) AND stream = "Teststream"', [$localCategory]);
            $this->assertNotFalse($streamAssignment);
        }
        $this->assertEquals([2222,3333,4444], $mappedRemoteCategories);
    }

    public function testResolveReturnsNothingWithWrongStream()
    {
        $this->manager->getConnection()->executeQuery('DELETE FROM s_categories');
        $this->manager->getConnection()->executeQuery('DELETE FROM s_plugin_connect_categories');
        $this->manager->getConnection()->executeQuery('DELETE FROM s_plugin_connect_categories_to_local_categories');
        $this->importFixtures(__DIR__ . '/../_fixtures/one_connect_to_local_category.sql');
        $categories = [
            '/deutsch' => 'Deutsch',
            '/deutsch/test1' => 'Test 1',
            '/deutsch/test2' => 'Test 2'
        ];

        $localCategories = $this->categoryResolver->resolve($categories, 1234, 'Awesome Stream Test');

        $this->assertEmpty($localCategories);
    }

    public function testResolveReturnsNothingWithWrongShop()
    {
        $this->manager->getConnection()->executeQuery('DELETE FROM s_categories');
        $this->manager->getConnection()->executeQuery('DELETE FROM s_plugin_connect_categories');
        $this->manager->getConnection()->executeQuery('DELETE FROM s_plugin_connect_categories_to_local_categories');
        $this->importFixtures(__DIR__ . '/../_fixtures/one_connect_to_local_category.sql');
        $categories = [
            '/deutsch' => 'Deutsch',
            '/deutsch/test1' => 'Test 1',
            '/deutsch/test2' => 'Test 2'
        ];

        $localCategories = $this->categoryResolver->resolve($categories, 1, 'Teststream');

        $this->assertEmpty($localCategories);
    }
}
