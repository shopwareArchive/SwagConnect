<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Tests\Functional\Subscribers;

use Doctrine\DBAL\Connection;
use Shopware\Models\Article\Article;
use Shopware\Models\Article\Detail;
use ShopwarePlugins\Connect\Tests\TestClient;
use ShopwarePlugins\Connect\Tests\WebTestCaseTrait;
use Symfony\Component\HttpFoundation\Response;

class LifecycleTest extends \PHPUnit_Framework_TestCase
{
    use WebTestCaseTrait;

    /**
     * @param Response $response
     * @return array
     */
    private function handleJsonResponse(Response $response)
    {
        $this->assertEquals(200, $response->getStatusCode());
        $responseData = \json_decode($response->getContent(), true);
        $this->assertNotEmpty($responseData, 'Response is not valid JSON');

        return $responseData;
    }

    public function test_generate_delete_change_for_variants()
    {
        /** @var TestClient $client */
        $client = $this->createBackendClient();

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
        $client = $this->createBackendClient();

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
}
