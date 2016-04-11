<?php

use Tests\ShopwarePlugins\Connect\ConnectTestHelper;
use ShopwarePlugins\Connect\Components\ProductStream\ProductStreamRepository;
use ShopwarePlugins\Connect\Components\ProductStream\ProductStreamService;

class ProductStreamServiceTest extends ConnectTestHelper
{
    public $db;

    /**
     * @var integer
     */
    public $streamAId;

    public $streamBId;

    public $streamCId;

    /**
     * @var ProductStreamService
     */
    public $productStreamService;

    public function setUp()
    {
        $manager = Shopware()->Models();
        $this->productStreamService = new ProductStreamService(
            new ProductStreamRepository($manager),
            $manager->getRepository('Shopware\CustomModels\Connect\ProductStreamAttribute')
        );

        $this->insertDummyData();
    }

    private function insertDummyData()
    {
        $this->db = Shopware()->Db();
        $this->db->insert('s_product_streams', array('name' => 'TestProductStreamA', 'type' => 2));
        $this->streamAId = $this->db->lastInsertId();

        $this->db->insert('s_product_streams', array('name' => 'TestProductStreamB', 'type' => 2));
        $this->streamBId = $this->db->lastInsertId();

        $this->db->insert('s_product_streams', array('name' => 'TestProductStreamC', 'type' => 2));
        $this->streamCId = $this->db->lastInsertId();

        $this->db->insert(
            's_plugin_connect_streams',
            array('stream_id' => $this->streamAId, 'export_status' => ProductStreamService::STATUS_SUCCESS)
        );
        $this->db->insert(
            's_plugin_connect_streams',
            array('stream_id' => $this->streamBId, 'export_status' => ProductStreamService::STATUS_SUCCESS)
        );

        $articleAIds = array(33, 34, 35, 36);
        foreach ($articleAIds as $articleAId) {
            $this->db->insert('s_product_streams_selection',
                array('stream_id' => $this->streamAId, 'article_id' => $articleAId));
        }

        $articleBIds = array(35, 36, 37);
        foreach ($articleBIds as $articleBId) {
            $this->db->insert('s_product_streams_selection',
                array('stream_id' => $this->streamBId, 'article_id' => $articleBId));
        }

        $articleCIds = array(36);
        foreach ($articleCIds as $articleCId) {
            $this->db->insert('s_product_streams_selection',
                array('stream_id' => $this->streamCId, 'article_id' => $articleCId));
        }
    }

    public function tearDown()
    {
        $this->db->delete('s_product_streams', array('id = ?' => $this->streamAId));
        $this->db->delete('s_product_streams', array('id = ?' => $this->streamBId));
        $this->db->delete('s_product_streams', array('id = ?' => $this->streamCId));
        $this->db->delete('s_plugin_connect_streams', array('stream_id = ?' => $this->streamAId));
        $this->db->delete('s_plugin_connect_streams', array('stream_id = ?' => $this->streamBId));
    }

    public function testGetArticlesIds()
    {
        $stream = $this->productStreamService->findStream($this->streamAId);
        $articlesIds = $this->productStreamService->getArticlesIds($stream);

        $this->assertCount(4, $articlesIds);
        $this->assertTrue(in_array(33, $articlesIds));
    }

    public function testPrepareStreamsAssignments()
    {
        $streamsAssignments = $this->productStreamService->prepareStreamsAssignments($this->streamCId);

        $this->assertNull($streamsAssignments->getStreamsByArticleId(35));
        $this->assertCount(3, $streamsAssignments->getStreamsByArticleId(36));
        $this->assertCount(1, $streamsAssignments->getArticleIds());
    }

    public function testRemoveProductsFromStream()
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