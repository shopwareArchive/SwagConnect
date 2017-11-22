<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Functional\Controller;

use ShopwarePlugins\Connect\Tests\WebTestCaseTrait;
use ShopwarePlugins\Connect\Tests\TestClient;

class ConnectTest extends \PHPUnit_Framework_TestCase
{
    use WebTestCaseTrait;

    public function test_get_article_source_ids()
    {
        /** @var TestClient $client */
        $client = self::createBackendClient();

        $this->importFixturesFileOnce(__DIR__ . '/_fixtures/config_fixes.sql');
        $this->importFixturesFileOnce(__DIR__ . '/_fixtures/connect_items.sql');
        $expectedIds = ['2', '2-123', '2-124'];

        $client->request('POST', 'backend/Connect/getArticleSourceIds', ['ids' => [2]]);
        $responseData = $this->handleJsonResponse($client->getResponse());

        $this->assertNotEmpty($responseData);
        $this->assertEquals(1, $responseData['success']);
        $this->assertEquals($expectedIds, $responseData['sourceIds']);
    }

    public function test_get_article_count()
    {
        /** @var TestClient $client */
        $client = self::createBackendClient();

        $this->importFixturesFileOnce(__DIR__ . '/_fixtures/config_fixes.sql');
        $this->importFixturesFileOnce(__DIR__ . '/_fixtures/connect_items.sql');
        $articleCount = 19;

        $client->request('POST', 'backend/Connect/getArticleCount');
        $responseData = $this->handleJsonResponse($client->getResponse());

        $this->assertNotEmpty($responseData);
        $this->assertTrue($responseData['success']);
        $this->assertEquals($articleCount, $responseData['count']);
    }

    public function test_exporting_a_product_with_variants()
    {
        /** @var TestClient $client */
        $client = self::createBackendClient();

        $this->importFixturesFileOnce(__DIR__ . '/_fixtures/config_fixes.sql');
        $this->importFixturesFileOnce(__DIR__ . '/_fixtures/connect_items.sql');

        $sourceIds = ['2','2-123','2-124'];

        $client->request('POST', 'backend/Connect/insertOrUpdateProduct', ['sourceIds' => $sourceIds]);
        $responseData = $this->handleJsonResponse($client->getResponse());

        $this->assertTrue($responseData['success']);

        $exportedItems = self::getDbalConnection()->fetchAll(
            "SELECT export_status FROM s_plugin_connect_items WHERE source_id in (?, ?, ?) AND export_status = 'update' ",
            $sourceIds
        );
        $this->assertCount(3, $exportedItems);
    }

    public function test_error_handling_on_export()
    {
        /** @var TestClient $client */
        $client = self::createBackendClient();

        $this->importFixturesFileOnce(__DIR__ . '/_fixtures/config_fixes.sql');
        $this->importFixturesFileOnce(__DIR__ . '/_fixtures/connect_items.sql');

        $connection = self::getDbalConnection();

        $connection->executeQuery('DELETE FROM s_articles_prices WHERE articledetailsID = 123');

        $client->request('POST', 'backend/Connect/insertOrUpdateProduct', ['sourceIds' => ['2','2-123']]);
        $responseData = $this->handleJsonResponse($client->getResponse());

        $this->assertFalse($responseData['success']);
        $this->assertCount(1, $responseData['messages']['price']);

        $exportedItems = $connection->fetchAll("SELECT export_status FROM s_plugin_connect_items WHERE source_id = '2' AND export_status = 'update' ");
        $this->assertCount(1, $exportedItems);
    }

    public function test_mark_product_to_be_deleted()
    {
        /** @var TestClient $client */
        $client = self::createBackendClient();

        $this->importFixturesFileOnce(__DIR__ . '/_fixtures/config_fixes.sql');
        $this->importFixturesFileOnce(__DIR__ . '/_fixtures/connect_items.sql');

        $client->request('POST', 'backend/Connect/deleteProduct', ['ids' => ['2','2-123','2-124']]);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertEquals('[]', $client->getResponse()->getContent());
    }

    public function test_get_export_list()
    {
        /** @var TestClient $client */
        $client = self::createBackendClient();

        $this->importFixturesFileOnce(__DIR__ . '/_fixtures/config_fixes.sql');
        $this->importFixturesFileOnce(__DIR__ . '/_fixtures/connect_items.sql');

        $client->request('GET', 'backend/Connect/getExportList', ['page' => 0, 'start' => 0, 'limit' => 20]);
        $responseData = $this->handleJsonResponse($client->getResponse());

        $this->assertTrue($responseData['success']);
        $this->assertCount(2, $responseData['data']);
    }

    public function test_get_export_status()
    {
        /** @var TestClient $client */
        $client = self::createBackendClient();

        $this->importFixturesFileOnce(__DIR__ . '/_fixtures/config_fixes.sql');
        $this->importFixturesFileOnce(__DIR__ . '/_fixtures/connect_items.sql');

        $exportedProductCount = 14;
        $totalProductCount = 19;

        $client->request('GET', 'backend/Connect/getExportStatus');
        $responseData = $this->handleJsonResponse($client->getResponse());

        $this->assertTrue($responseData['success']);
        $this->assertEquals($exportedProductCount, $responseData['data']);
        $this->assertEquals($totalProductCount, $responseData['total']);
    }

    public function test_get_stream_list()
    {
        /** @var TestClient $client */
        $client = self::createBackendClient();

        $this->importFixturesFileOnce(__DIR__ . '/_fixtures/config_fixes.sql');
        $this->importFixturesFileOnce(__DIR__ . '/_fixtures/sw_product_stream.sql');

        $streamName = 'AwStream';

        $client->request(
            'GET',
            'backend/Connect/getStreamList',
            [
                'page' => 0,
                'start' => 0,
                'limit' => 20,
                'group' => '{"property":"type","direction":"ASC"}',
                'sort' => '{"property":"type","direction":"ASC"}'
            ]
        );
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->assertContains('"success":true', $client->getResponse()->getContent());
        $this->assertContains($streamName, $client->getResponse()->getContent());
    }

    public function test_get_stream_product_count()
    {
        /** @var TestClient $client */
        $client = self::createBackendClient();

        $this->importFixturesFileOnce(__DIR__ . '/_fixtures/config_fixes.sql');
        $this->importFixturesFileOnce(__DIR__ . '/_fixtures/connect_items.sql');
        $this->importFixturesFileOnce(__DIR__ . '/_fixtures/sw_product_stream.sql');

        $streamId = self::getDbalConnection()->fetchColumn('SELECT id FROM s_product_streams WHERE name = ?', ['AwStream']);

        $streamProductCount = 19;

        $client->request('POST', 'backend/Connect/getStreamProductsCount', ['ids' => [$streamId]]);
        $responseData = $this->handleJsonResponse($client->getResponse());

        $this->assertTrue($responseData['success']);
        $this->assertEquals($streamProductCount, $responseData['sourceIdsCount']);
    }

    public function test_export_stream()
    {
        /** @var TestClient $client */
        $client = self::createBackendClient();

        $this->importFixturesFileOnce(__DIR__ . '/_fixtures/config_fixes.sql');
        $this->importFixturesFileOnce(__DIR__ . '/_fixtures/connect_items.sql');
        $this->importFixturesFileOnce(__DIR__ . '/_fixtures/sw_product_stream.sql');

        $connection = self::getDbalConnection();
        $itemsInStream = 19;

        $streamId = $connection->fetchColumn('SELECT id FROM s_product_streams WHERE name = ?', ['AwStream']);

        $client->request('POST', 'backend/Connect/exportStream', ['offset' => 0, 'limit' => 50, 'streamIds' => [$streamId]]);
        $responseData = $this->handleJsonResponse($client->getResponse());

        $this->assertTrue($responseData['success']);

        $exportedItems = self::getDbalConnection()->fetchAll(
            "SELECT export_status FROM s_plugin_connect_items WHERE export_status = 'update' "
        );
        $this->assertCount($itemsInStream, $exportedItems);
    }
}
