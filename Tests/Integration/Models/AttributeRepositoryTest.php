<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Tests\Integration\Models;

use Shopware\CustomModels\Connect\Attribute;
use Shopware\CustomModels\Connect\AttributeRepository;
use ShopwarePlugins\Connect\Tests\DatabaseTestCaseTrait;
use Doctrine\DBAL\Connection;

class AttributeRepositoryTest extends \PHPUnit_Framework_TestCase
{
    use DatabaseTestCaseTrait;

    /**
     * @var AttributeRepository
     */
    private $repository;

    /**
     * @var Connection
     */
    private $connection;

    public function setUp()
    {
        $this->repository = Shopware()->Models()->getRepository(Attribute::class);
        $this->connection = Shopware()->Container()->get('dbal_connection');
    }

    public function test_find_source_ids_of_products()
    {
        $this->connection->executeQuery('DELETE FROM s_plugin_connect_items');
        $this->connection->executeQuery('DELETE FROM s_articles_details');
        $this->importFixtures(__DIR__ . '/../_fixtures/simple_connect_items.sql');

        $articleIds = $expectedSourceIds = [14467, 14468];
        $this->assertEquals($expectedSourceIds, $this->repository->findSourceIds($articleIds, 1));
    }

    public function test_find_source_ids_of_variants()
    {
        $this->connection->executeQuery('DELETE FROM s_plugin_connect_items');
        $this->connection->executeQuery('DELETE FROM s_articles_details');
        $this->importFixtures(__DIR__ . '/../_fixtures/simple_connect_items.sql');

        $articleIds = $mainSourceIds = [14469, 14470];
        $sourceIdsForVariants = [
            '14469-7091849',
            '14470-7091851',
            '14470-7091852',
        ];
        $this->assertEquals($mainSourceIds, $this->repository->findSourceIds($articleIds, 1));
        $this->assertEquals($sourceIdsForVariants, $this->repository->findSourceIds($articleIds, 2));
    }

    public function test_get_local_article_count()
    {
        $this->connection->executeQuery('DELETE FROM s_plugin_connect_items');
        $this->connection->executeQuery('DELETE FROM s_articles_details');
        $this->importFixtures(__DIR__ . '/../_fixtures/simple_connect_items.sql');
        $count = $this->repository->getLocalArticleCount();
        $this->assertEquals(8, $count);
    }

    public function test_find_all_source_ids()
    {
        $this->connection->executeQuery('DELETE FROM s_plugin_connect_items');
        $this->connection->executeQuery('DELETE FROM s_articles_details');
        $this->importFixtures(__DIR__ . '/../_fixtures/simple_connect_items.sql');

        $mainProductIds = $this->repository->findAllSourceIds(0, 5);
        $variantIds = $this->repository->findAllSourceIds(5, 3);

        $expectedProductIds = ['14467', '14468', '14469', '14470', '14471'];
        $expectedVariantIds = ['14469-7091849', '14470-7091851', '14470-7091852'];

        $this->assertEquals($expectedProductIds, $mainProductIds);
        $this->assertEquals($expectedVariantIds, $variantIds);
    }
}
