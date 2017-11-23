<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Tests\Functional\Controller;

use Doctrine\DBAL\Connection;
use ShopwarePlugins\Connect\Tests\TestClient;
use ShopwarePlugins\Connect\Tests\WebTestCaseTrait;

class ImportTest extends \PHPUnit_Framework_TestCase
{
    use WebTestCaseTrait;

    public function test_get_imported_product_categories_tree_default()
    {
        /** @var TestClient $client */
        $client = self::createBackendClient();

        $client->request('GET', 'backend/Import/getImportedProductCategoriesTree');
        $responseData = $this->handleJsonResponse($client->getResponse());

        $this->assertTrue($responseData['success']);
        $this->assertTrue(is_array($responseData['data']), 'Returned data must be array');
    }

    public function test_get_imported_product_categories_tree_with_params()
    {
        /** @var TestClient $client */
        $client = self::createBackendClient();

        $client->request(
            'POST',
            'backend/Import/getImportedProductCategoriesTree',
            [
                'categoryId' => '/deutsch/boots/nike',
                'id' => 'shopId6~stream~Awesome Products~/deutsch/boots/nike'
            ]
        );
        $responseData = $this->handleJsonResponse($client->getResponse());

        $this->assertTrue($responseData['success']);
    }

    public function test_assign_remote_to_local_category_action_without_params()
    {
        /** @var TestClient $client */
        $client = self::createBackendClient();

        $client->request('GET', 'backend/Import/assignRemoteToLocalCategory');

        $responseData = $this->handleJsonResponse($client->getResponse());

        $this->assertFalse($responseData['success']);
    }

    public function test_assign_remote_to_local_category()
    {
        /** @var TestClient $client */
        $client = self::createBackendClient();

        $connection = self::getDbalConnection();

        $this->importFixturesFileOnce(__DIR__ . '/../_fixtures/categories.sql');

        $localCategoryId = '140809703';
        $remoteCategoryKey = '/deutsch/television';

        $remoteCategoryId = $connection->fetchColumn('SELECT id FROM s_plugin_connect_categories WHERE category_key = ? AND shop_id = 1234', [$remoteCategoryKey]);

        $client->request(
            'POST',
            'backend/Import/assignRemoteToLocalCategory',
            [
                'localCategoryId' => $localCategoryId,
                'remoteCategoryKey' => $remoteCategoryKey,
                'remoteCategoryLabel' => 'Television',
                'node' => 'shopId1234~stream~Awesome Products~/deutsch/television'
            ]
        );
        $responseData = $this->handleJsonResponse($client->getResponse());

        $this->assertTrue($responseData['success']);

        $categoryAssignment = self::getDbalConnection()->fetchAssoc(
            'SELECT cc_to_lc.remote_category_id, lc.parent FROM s_plugin_connect_categories_to_local_categories cc_to_lc LEFT JOIN s_categories lc ON lc.id = cc_to_lc.local_category_id',
            [$localCategoryId]
        );

        $this->assertEquals($remoteCategoryId, $categoryAssignment['remote_category_id']);
        $this->assertEquals($localCategoryId, $categoryAssignment['parent']);
    }

    /**
     * @test
     */
    public function get_imported_product_categories_tree_when_parent_is_numeric()
    {
        /** @var TestClient $client */
        $client = self::createBackendClient();

        $client->request('POST', 'backend/Import/getImportedProductCategoriesTree', ['id' => 4]);
        $responseData = $this->handleJsonResponse($client->getResponse());

        $this->assertTrue($responseData['success']);
        $this->assertTrue(is_array($responseData['data']), 'Returned data must be array');
    }

    /**
     * @test
     */
    public function get_imported_product_categories_tree_when_parent_is_stream()
    {
        /** @var TestClient $client */
        $client = self::createBackendClient();

        $client->request('POST', 'backend/Import/getImportedProductCategoriesTree', ['id' => '3_stream_Awesome products']);
        $responseData = $this->handleJsonResponse($client->getResponse());

        $this->assertTrue($responseData['success']);
        $this->assertTrue(is_array($responseData['data']), 'Returned data must be array');
    }

    /**
     * @test
     */
    public function get_imported_product_categories_tree_when_parent_is_category()
    {
        /** @var TestClient $client */
        $client = self::createBackendClient();

        $client->request('POST', 'backend/Import/getImportedProductCategoriesTree', ['id' => '/bücher']);
        $responseData = $this->handleJsonResponse($client->getResponse());

        $this->assertTrue($responseData['success']);
        $this->assertTrue(is_array($responseData['data']), 'Returned data must be array');
    }

    /**
     * @test
     */
    public function load_articles_by_remote_category_with_empty_category()
    {
        /** @var TestClient $client */
        $client = self::createBackendClient();

        $client->request('POST', 'backend/Import/loadArticlesByRemoteCategory', ['shopId' => '3']);
        $responseData = $this->handleJsonResponse($client->getResponse());

        $this->assertTrue($responseData['success']);
        $this->assertTrue(is_array($responseData['data']), 'Returned data must be array');
    }

    /**
     * @test
     */
    public function load_articles_by_remote_category_with_stream()
    {

        /** @var TestClient $client */
        $client = self::createBackendClient();

        $client->request(
            'POST',
            'backend/Import/loadArticlesByRemoteCategory',
            [
                'shopId' => '3',
                'category' => '3_stream_Awesome products'
            ]
        );
        $responseData = $this->handleJsonResponse($client->getResponse());

        $this->assertFalse($responseData['success']);
        $this->assertTrue(is_string($responseData['message']), 'Returned message must a string');
    }

    /**
     * @test
     */
    public function load_articles_by_remote_category_with_empty_shop_id()
    {
        /** @var TestClient $client */
        $client = self::createBackendClient();

        $client->request('POST', 'backend/Import/loadArticlesByRemoteCategory', ['category' => 'Awesome products']);
        $responseData = $this->handleJsonResponse($client->getResponse());

        $this->assertTrue($responseData['success']);
        $this->assertTrue(is_array($responseData['data']), 'Returned data must be array');
    }

    public function test_unassign_remote_to_local_category()
    {
        /** @var TestClient $client */
        $client = self::createBackendClient();

        $client->request('POST', 'backend/Import/unassignRemoteToLocalCategory', ['localCategoryId' => 6]);
        $responseData = $this->handleJsonResponse($client->getResponse());

        $this->assertTrue($responseData['success']);
    }

    public function test_unassign_remote_to_local_category_without_category_id()
    {
        /** @var TestClient $client */
        $client = self::createBackendClient();

        $client->request('POST', 'backend/Import/unassignRemoteToLocalCategory', ['localCategoryId']);
        $responseData = $this->handleJsonResponse($client->getResponse());

        $this->assertFalse($responseData['success']);
        $this->assertEquals('Invalid local or remote category', $responseData['error']);
    }

    public function test_unassign_remote_articles_from_local_category()
    {
        /** @var TestClient $client */
        $client = self::createBackendClient();

        $this->importFixturesFileOnce(__DIR__ . '/../_fixtures/categories.sql');

        $categoryID = 140809703;
        $productId = '2';

        $connection = self::getDbalConnection();

        $client->request('POST', 'backend/Import/unassignRemoteArticlesFromLocalCategory', ['articleIds' => [$productId], 'categoryId' => $categoryID]);
        $responseData = $this->handleJsonResponse($client->getResponse());

        $this->assertTrue($responseData['success']);

        $categoryAssignment = $connection->fetchAll('SELECT * FROM s_articles_categories WHERE articleID = ? AND categoryID = ?', [$productId, $categoryID]);
        $this->assertEmpty($categoryAssignment);

        $isProductConnectMapped = $connection->fetchColumn('SELECT connect_mapped_category FROM s_articles_attributes WHERE articleID = ?', [$productId]);
        $this->assertNull($isProductConnectMapped);
    }

    public function test_recreate_remote_categories()
    {
        /** @var TestClient $client */
        $client = self::createBackendClient();

        $this->importFixturesFileOnce(__DIR__ . '/_fixtures/connect_items.sql');

        $articleIDs = [2, 117];

        self::getDbalConnection()->executeQuery('DELETE FROM s_plugin_connect_product_to_categories WHERE articleID IN (?)', [$articleIDs], [Connection::PARAM_INT_ARRAY]);
        self::getDbalConnection()->executeQuery('UPDATE s_plugin_connect_items SET shop_id = 2');

        $client->request('POST', 'backend/Import/recreateRemoteCategories');
        $responseData = $this->handleJsonResponse($client->getResponse());
        $this->assertTrue($responseData['success']);

        $assignedProducts = self::getDbalConnection()->fetchAll('SELECT id FROM s_plugin_connect_product_to_categories WHERE articleID IN (?)', [$articleIDs], [Connection::PARAM_INT_ARRAY]);

        $this->assertNotEmpty($assignedProducts);
    }
}
