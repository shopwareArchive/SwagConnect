<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Tests\Integration\Components\ProductStream;

use Shopware\Bundle\SearchBundle\ProductSearchInterface;
use Shopware\Bundle\StoreFrontBundle\Service\ContextServiceInterface;
use Shopware\CustomModels\Connect\ProductStreamAttribute;
use Shopware\CustomModels\Connect\ProductStreamAttributeRepository;
use ShopwarePlugins\Connect\Components\Config;
use ShopwarePlugins\Connect\Components\ProductStream\ProductStreamRepository;
use ShopwarePlugins\Connect\Components\ProductStream\ProductStreamService;
use ShopwarePlugins\Connect\Tests\DatabaseTestCaseTrait;
use Doctrine\DBAL\Connection;

class ProductStreamServiceTest extends \PHPUnit_Framework_TestCase
{
    use DatabaseTestCaseTrait;

    /**
     * @var ProductStreamService
     */
    private $productStreamService;

    private $productStreamAttributeRepository;

    /**
     * @var Connection $connection
     */
    private $connection;

    public function setUp()
    {
        $this->productStreamAttributeRepository = Shopware()->Models()->getRepository(ProductStreamAttribute::class);
        $this->productStreamService = new ProductStreamService(
            new ProductStreamRepository(
                Shopware()->Models(),
                Shopware()->Container()->get('shopware_product_stream.repository')
            ),
            $this->productStreamAttributeRepository,
            $this->createMock(Config::class),
            $this->createMock(ProductSearchInterface::class),
            $this->createMock(ContextServiceInterface::class)
        );

        $this->connection = Shopware()->Container()->get('dbal_connection');
        $this->importFixtures(__DIR__ . '/_fixtures/products_and_streams_relation.sql');
    }

    public function test_count_products_in_static_stream()
    {
        $streamId = $this->connection->fetchColumn("SELECT id FROM s_product_streams WHERE `name` = 'Küche'");

        $this->assertEquals(3, $this->productStreamService->countProductsInStaticStream($streamId));
    }

    public function test_count_products_in_multiple_static_streams()
    {
        $kitchenStreamId = $this->connection->fetchColumn("SELECT id FROM s_product_streams WHERE `name` = 'Küche'");
        $livingRoomStreamId = $this->connection->fetchColumn("SELECT id FROM s_product_streams WHERE `name` = 'Wohnzimmer'");

        $this->assertEquals(3, $this->productStreamService->countProductsInStaticStream($kitchenStreamId));
        $this->assertEquals(4, $this->productStreamService->countProductsInStaticStream($livingRoomStreamId));
        $this->assertEquals(7, $this->productStreamService->countProductsInStaticStream([$kitchenStreamId, $livingRoomStreamId]));
    }

    public function test_set_stream_export_status()
    {
        $streamId = $this->connection->fetchColumn("SELECT id FROM s_product_streams WHERE `name` = 'Wohnzimmer'");
        $this->productStreamService->changeStatus($streamId, ProductStreamService::STATUS_EXPORT);

        $result = $this->connection->fetchAll('SELECT export_status, export_message FROM s_plugin_connect_streams WHERE stream_id = ?', [$streamId]);

        $this->assertEquals(ProductStreamService::STATUS_EXPORT, $result[0]['export_status']);
    }

    public function test_set_stream_export_status_and_message()
    {
        $exportMessage = 'Success';
        $streamId = $this->connection->fetchColumn("SELECT id FROM s_product_streams WHERE `name` = 'Wohnzimmer'");
        $this->productStreamService->changeStatus($streamId, ProductStreamService::STATUS_EXPORT, $exportMessage);

        $result = $this->connection->fetchAll('SELECT export_status, export_message FROM s_plugin_connect_streams WHERE stream_id = ?', [$streamId]);

        $this->assertEquals(ProductStreamService::STATUS_EXPORT, $result[0]['export_status']);
        $this->assertEquals($exportMessage, $result[0]['export_message']);
    }
}
