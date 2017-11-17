<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Tests\Functional\Subscriber;

use ShopwarePlugins\Connect\Tests\WebTestCaseTrait;

class ArticleTest extends \PHPUnit_Framework_TestCase
{
    use WebTestCaseTrait;

    public function testExtendBackendArticlePropertyGroup()
    {
        /** @var TestClient $client */
        $client = $this->createBackendClient();

        $this->importFixturesFileOnce(__DIR__ . '/_fixtures/articleWithPriceGroup.sql');

        $client->request('POST', 'backend/Article/deleteDetail', ['articleId' => 32870]);

        $responseData = $this->handleJsonResponse($client->getResponse());

        $this->assertTrue($responseData['success']);

        $statuses = self::getDbalConnection()->fetchColumn(
            'SELECT export_status FROM s_plugin_connect_items WHERE article_id = ?',
            [32870]
        );

        $this->assertNotEmpty($statuses);

        foreach ($statuses as $status) {
            //doesn't matter that theres an error
            //but this verifys that the export of the articles has run
            //we had an bug there: https://jira.shopware.com/browse/CON-4955
            self::assertEquals('error', $status);
        }
    }
}
