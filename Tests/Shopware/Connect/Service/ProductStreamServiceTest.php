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
    public $streamId;

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
        $this->db->insert('s_product_streams', array('name' => 'TestProductStream', 'type' => 2));
        $this->streamId = $this->db->lastInsertId();

        $articleIds = array(3, 4, 5, 6);

        foreach ($articleIds as $articleId) {
            $this->db->insert('s_product_streams_selection',
                array('stream_id' => $this->streamId, 'article_id' => $articleId));
        }
    }

    public function tearDown()
    {
        $this->db->delete('s_product_streams_selection', array('stream_id = ?' => $this->streamId));
        $this->db->delete('s_product_streams', array('id = ?' => $this->streamId));
    }

    public function testGetArticlesIds()
    {
        $articlesIds = $this->productStreamService->getArticlesIds($this->streamId);

        $this->assertCount(4, $articlesIds);
        $this->assertTrue(in_array(3, $articlesIds));
    }

    /**
     * @expectedException Doctrine\ORM\NoResultException
     */
    public function testNotExistingStream()
    {
        $this->productStreamService->getArticlesIds(99999);
    }

}