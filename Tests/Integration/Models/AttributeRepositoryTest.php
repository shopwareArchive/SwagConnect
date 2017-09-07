<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Tests\Integration\Models;

use Shopware\CustomModels\Connect\Attribute;
use ShopwarePlugins\Connect\Tests\DatabaseTestCaseTrait;
use Doctrine\DBAL\Connection;

class AttributeRepositoryTest extends \PHPUnit_Framework_TestCase
{
    use DatabaseTestCaseTrait;

    private $repository;

    /**
     * @var Connection $connection
     */
    private $connection;

    public function setUp()
    {
        $this->repository = Shopware()->Models()->getRepository(Attribute::class);
        $this->connection = Shopware()->Container()->get('dbal_connection');
    }

    public function test_find_source_ids_of_products()
    {
        $this->importFixtures(__DIR__ . '/_fixtures/simple_connect_items.sql');

        $articleIds = $expectedSourceIds = [14467, 14468];
        $this->assertEquals($expectedSourceIds, $this->repository->findSourceIds($articleIds, 1));
    }

    public function test_find_source_ids_of_variants()
    {
        $this->importFixtures(__DIR__ . '/_fixtures/simple_connect_items.sql');

        $articleIds = $mainSourceIds = [14469, 14470];
        $sourceIdsForVariants = [
            '14469-7091849',
            '14470-7091851',
            '14470-7091852',
        ];
        $this->assertEquals($mainSourceIds, $this->repository->findSourceIds($articleIds, 1));
        $this->assertEquals($sourceIdsForVariants, $this->repository->findSourceIds($articleIds, 2));
    }
}
