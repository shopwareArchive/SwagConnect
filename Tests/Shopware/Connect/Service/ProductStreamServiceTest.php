<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Tests\ShopwarePlugins\Connect\ConnectTestHelper;
use ShopwarePlugins\Connect\Components\ProductStream\ProductStreamRepository;
use ShopwarePlugins\Connect\Components\ProductStream\ProductStreamService;
use ShopwarePlugins\Connect\Components\Config;

class ProductStreamServiceTest extends ConnectTestHelper
{
    public $db;

    /**
     * @var int
     */
    public $streamAId;

    public $streamBId;

    public $streamCId;

    public $streamDId;

    /**
     * @var ProductStreamService
     */
    public $productStreamService;

    public function setUp()
    {
        parent::setUp();

        $manager = Shopware()->Models();
        $container = Shopware()->Container();
        $this->productStreamService = new ProductStreamService(
            new ProductStreamRepository($manager),
            $manager->getRepository('Shopware\CustomModels\Connect\ProductStreamAttribute'),
            new Config($manager),
            $container->get('shopware_search.product_search'),
            $container->get('shopware_storefront.context_service')
        );

        $this->insertDummyData();
    }

    private function insertDummyData()
    {
        $this->db = Shopware()->Db();
        $this->db->insert('s_product_streams', ['name' => 'TestProductStreamA', 'type' => 2]);
        $this->streamAId = $this->db->lastInsertId();

        $this->db->insert('s_product_streams', ['name' => 'TestProductStreamB', 'type' => 2]);
        $this->streamBId = $this->db->lastInsertId();

        $this->db->insert('s_product_streams', ['name' => 'TestProductStreamC', 'type' => 2]);
        $this->streamCId = $this->db->lastInsertId();

        $this->db->insert('s_product_streams', ['name' => 'TestProductStreamD', 'type' => 1]);
        $this->streamDId = $this->db->lastInsertId();

        $this->db->insert(
            's_plugin_connect_streams',
            ['stream_id' => $this->streamAId, 'export_status' => ProductStreamService::STATUS_EXPORT]
        );
        $this->db->insert(
            's_plugin_connect_streams',
            ['stream_id' => $this->streamBId, 'export_status' => ProductStreamService::STATUS_EXPORT]
        );
        $this->db->insert(
            's_plugin_connect_streams',
            ['stream_id' => $this->streamDId, 'export_status' => ProductStreamService::STATUS_EXPORT]
        );

        $articleAIds = [33, 34, 35, 36];
        foreach ($articleAIds as $articleAId) {
            $this->db->insert('s_product_streams_selection',
                ['stream_id' => $this->streamAId, 'article_id' => $articleAId]);
        }

        $articleBIds = [35, 36, 37];
        foreach ($articleBIds as $articleBId) {
            $this->db->insert('s_product_streams_selection',
                ['stream_id' => $this->streamBId, 'article_id' => $articleBId]);
        }

        $articleCIds = [36];
        foreach ($articleCIds as $articleCId) {
            $this->db->insert('s_product_streams_selection',
                ['stream_id' => $this->streamCId, 'article_id' => $articleCId]);
        }
    }

    public function tearDown()
    {
        $this->db->delete('s_product_streams', ['id = ?' => $this->streamAId]);
        $this->db->delete('s_product_streams', ['id = ?' => $this->streamBId]);
        $this->db->delete('s_product_streams', ['id = ?' => $this->streamCId]);
        $this->db->delete('s_product_streams', ['id = ?' => $this->streamDId]);
        $this->db->delete('s_plugin_connect_streams', ['stream_id = ?' => $this->streamAId]);
        $this->db->delete('s_plugin_connect_streams', ['stream_id = ?' => $this->streamBId]);
        $this->db->delete('s_plugin_connect_streams', ['stream_id = ?' => $this->streamDId]);
    }

    public function testGetArticlesIdsFromStaticStream()
    {
        $stream = $this->productStreamService->findStream($this->streamAId);
        $articlesIds = $this->productStreamService->getArticlesIds($stream);

        $this->assertCount(4, $articlesIds);
        $this->assertTrue(in_array(33, $articlesIds));
    }

    public function testGetArticlesIdsFromDynamicStream()
    {
        $expectedArticleIds = [35, 36];
        $this->productStreamService->createStreamRelation($this->streamDId, $expectedArticleIds);
        $stream = $this->productStreamService->findStream($this->streamDId);
        $articlesIds = $this->productStreamService->getArticlesIds($stream);

        $this->assertEquals($expectedArticleIds, $articlesIds);
    }

    public function testPrepareStreamsAssignments()
    {
        $this->productStreamService->createStreamRelation($this->streamDId, [35, 36]);
        $streamsAssignments = $this->productStreamService->prepareStreamsAssignments($this->streamCId);

        $this->assertNull($streamsAssignments->getStreamsByArticleId(35));
        $this->assertCount(4, $streamsAssignments->getStreamsByArticleId(36));
        $this->assertCount(1, $streamsAssignments->getArticleIds());
    }

    public function testPrepareStreamsAssignmentsWithRemovedArticleFromDynamicStream()
    {
        $this->productStreamService->createStreamRelation($this->streamDId, [7, 35, 36, 37]);
        $this->productStreamService->markProductsToBeRemovedFromStream($this->streamDId);

        //simulate the there is a changes in the dynamic stream conditions
        //and a product which was in this stream, now is not taking part anymore
        $this->productStreamService->createStreamRelation($this->streamDId, [35, 36]);

        $streamsAssignments = $this->productStreamService->prepareStreamsAssignments($this->streamDId, false);

        $this->assertNotEmpty($streamsAssignments->getStreamsByArticleId(35));
        $this->assertNotEmpty($streamsAssignments->getStreamsByArticleId(36));

        //its not empty because its part of static stream
        $this->assertNotEmpty($streamsAssignments->getStreamsByArticleId(37));

        //this product no longer takes a part in the stream
        $this->assertEmpty($streamsAssignments->getStreamsByArticleId(7));
        $this->assertCount(4, $streamsAssignments->getArticleIds());
    }

    public function testPrepareStreamsAssignmentsWithoutProductsInDynamicStream()
    {
        $this->productStreamService->createStreamRelation($this->streamDId, [7, 8]);
        $this->productStreamService->markProductsToBeRemovedFromStream($this->streamDId);

        $streamsAssignments = $this->productStreamService->prepareStreamsAssignments($this->streamDId, false);

        $this->assertCount(0, $streamsAssignments->getStreamsByArticleId(7));
        $this->assertCount(0, $streamsAssignments->getStreamsByArticleId(8));
        $this->assertCount(2, $streamsAssignments->getArticleIds());
    }

    public function testAllowToRemoveProductsFromStream()
    {
        $assignments = $this->productStreamService->getStreamAssignments($this->streamBId);

        foreach ($assignments->getArticleIds() as $articleId) {
            if ($this->productStreamService->allowToRemove($assignments, $this->streamBId, $articleId)) {
                $this->assertEquals(37, $articleId);
            }
        }
    }

    /**
     * @expectedException Doctrine\ORM\NoResultException
     */
    public function testNotExistingStream()
    {
        $this->productStreamService->findStream(99999);
    }
}
