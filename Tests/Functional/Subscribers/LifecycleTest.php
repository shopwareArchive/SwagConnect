<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Tests\Functional\Subscribers;

use Doctrine\DBAL\Connection;
use ShopwarePlugins\Connect\Components\ConfigFactory;
use ShopwarePlugins\Connect\Tests\TestClient;
use ShopwarePlugins\Connect\Tests\WebTestCaseTrait;

class LifecycleTest extends \PHPUnit_Framework_TestCase
{
    use WebTestCaseTrait;

    public function test_update_prices()
    {
        /** @var TestClient $client */
        $client = self::createBackendClient();

        $connection = self::getDbalConnection();

        $this->importFixturesFileOnce(__DIR__ . '/_fixtures/simple_variants.sql');

        $priceId = $connection->fetchColumn(
            'SELECT id FROM s_articles_prices WHERE articleID = ? AND articledetailsID = ?',
            ['32870', '2404537']
        );

        $client->request(
            'POST',
            'backend/Article/saveDetail',
            [
                'prices' => [
                    0 => [
                        'id' => $priceId,
                        'from' => 1,
                        'to' => 5,
                        'price' => 238.00,
                        'pseudoPrice' => 0,
                        'percent' => 0,
                        'cloned' => false,
                        'customerGroupKey' => 'EK',
                        'customerGroup' => [
                            0 => [
                                'id' => 1,
                                'key' => 'EK',
                                'name' => 'Shopkunden',
                                'tax' => true,
                                'taxInput' => true,
                                'mode' => false,
                                'discount' => 0
                            ]
                        ]
                    ]
                ],
                'controller' => 'Article',
                'module' => 'backend',
                'action' => 'saveDetail',
                'number' => 'sw32870.3',
                'price' => 238.00,
                'additionalText' => 'L / Schwarz',
                'supplierNumber' => '',
                'active' => true,
                'inStock' => 15,
                'stockMin' => 0,
                'weight' => 0,
                'kind' => 1,
                'position' => 0,
                'shippingFree' => false,
                'minPurchase' => 1,
                'purchasePrice' => 38.99,
                'articleId' => 32870,
                'standard' => false,
                'id' => 2404537,
            ]
        );

        $returnedData = $this->handleJsonResponse($client->getResponse());

        $this->assertTrue($returnedData['success']);

        $changes = $connection->fetchAll(
            'SELECT c_entity_id, c_operation, c_revision, c_payload FROM sw_connect_change WHERE c_entity_id = ?',
            ['32870-2404537']
        );
        $this->assertCount(1, $changes);
        $updateChange = $changes[0];
        $this->assertEquals('update', $updateChange['c_operation']);
        $product = unserialize($updateChange['c_payload']);
        $this->assertEquals(200.00, $product->price);
    }

    public function test_generate_delete_change_for_variants()
    {
        /** @var TestClient $client */
        $client = self::createBackendClient();

        $this->importFixturesFileOnce(__DIR__ . '/_fixtures/simple_variants.sql');

        $client->request('POST', 'backend/Article/deleteDetail', ['articleId' => 32870, 'id' => 2404537]);

        $responseData = $this->handleJsonResponse($client->getResponse());

        $this->assertTrue($responseData['success']);

        $changes = self::getDbalConnection()->fetchAll(
            'SELECT c_entity_id, c_operation, c_revision, c_payload FROM sw_connect_change WHERE c_entity_id = ?',
            ['32870-2404537']
        );

        $this->assertCount(1, $changes);
        $this->assertEquals('delete', $changes[0]['c_operation']);
    }

    public function test_generate_delete_change_only_for_exported()
    {
        /** @var TestClient $client */
        $client = self::createBackendClient();

        /** @var Connection $connection */
        $connection = self::getDbalConnection();

        $this->importFixturesFileOnce(__DIR__ . '/_fixtures/simple_variants.sql');

        $detailExist = $connection->fetchColumn(
            'SELECT COUNT(id) FROM s_articles_details WHERE id = ? AND articleID = ?',
            [2404544, 32871]
        );

        $this->assertEquals(1, $detailExist, 'Article detail does not exist!');

        $client->request('POST', 'backend/Article/deleteDetail', ['articleId' => 32870, 'id' => 2404537]);

        $responseData = $this->handleJsonResponse($client->getResponse());

        $this->assertTrue($responseData['success']);

        $changes = $connection->fetchAll(
            'SELECT c_entity_id, c_operation, c_revision, c_payload FROM sw_connect_change WHERE c_entity_id = ?',
            ['32871-2404544']
        );

        $this->assertEmpty($changes);
    }

    public function testRemoveExportedLanguageOnShopDelete()
    {

        /** @var TestClient $client */
        $client = self::createBackendClient();

        /** @var Connection $connection */
        $connection = self::getDbalConnection();

        $this->importFixturesFileOnce(__DIR__ . '/../_fixtures/shops.sql');
        $configComponent = ConfigFactory::getConfigInstance();
        $translationShops = array_map(
            function ($row) {
                return $row['id'];
            },
            $connection
            ->executeQuery('SELECT `id` FROM `s_core_shops` WHERE `name` = ? OR `name` = ?', ['Greek', 'Czech'])
            ->fetchAll()
        );
        $this->assertCount(2, $translationShops);

        $configComponent->setConfig('exportLanguages', $translationShops);

        $this->assertSame($translationShops, $configComponent->getConfig('exportLanguages'));

        $client->request('POST', 'backend/Config/deleteValues?_repositoryClass=shop', ['id' => $translationShops[0]]);

        $responseData = $this->handleJsonResponse($client->getResponse());

        $this->assertTrue($responseData['success']);
        $this->assertSame([$translationShops[1]], $configComponent->getConfig('exportLanguages'));
    }
}
