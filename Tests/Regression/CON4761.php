<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Tests\Regression;

use ShopwarePlugins\Connect\Components\CategoryExtractor;
use Shopware\CustomModels\Connect\AttributeRepository;
use ShopwarePlugins\Connect\Components\CategoryResolver;
use Shopware\Connect\Gateway;
use ShopwarePlugins\Connect\Components\RandomStringGenerator;
use Enlight_Components_Db_Adapter_Pdo_Mysql as Pdo;
use ShopwarePlugins\Connect\Tests\AbstractConnectUnitTest;

class CON4761 extends AbstractConnectUnitTest
{
    /**
     * @var CategoryExtractor
     */
    private $categoryExtractor;

    /**
     * @var CategoryResolver
     */
    private $categoryResolver;

    /**
     * @var Pdo
     */
    private $db;

    /**
     * @var RandomStringGenerator
     */
    private $randomStringGenerator;

    /**
     * @before
     */
    public function prepareMocks()
    {
        $this->categoryResolver = $this->createMock(CategoryResolver::class);
        $this->db = $this->createMock(Pdo::class);
        $this->randomStringGenerator = $this->createMock(RandomStringGenerator::class);

        $this->categoryExtractor = new CategoryExtractor(
            $this->createMock(AttributeRepository::class),
            $this->categoryResolver,
            $this->createMock(Gateway::class),
            $this->randomStringGenerator,
            $this->db
        );
    }

    /**
     * This test is about injecting stream name in category id
     * used for import category tree.
     * Category Id must have following structure when shopId and stream name are provided:
     *
     * shopId5~stream~Dress~/Kleidung/Mantel0981
     */
    public function testAlwaysInjectStreamName()
    {
        $parent = 'Kleidung';
        $rows = [
            '/Kleidung/Hosen' => 'Hosen',
            '/Kleidung/Mantel' => 'Mantel',
        ];

        $shopId = 5;
        $streamName = 'Dress';
        $sql = '
            SELECT pcc.category_key, pcc.label
            FROM `s_plugin_connect_categories` pcc
            INNER JOIN `s_plugin_connect_product_to_categories` pcptc
            ON pcptc.connect_category_id = pcc.id
            INNER JOIN `s_plugin_connect_items` pci
            ON pci.article_id = pcptc.articleID
            INNER JOIN `s_articles_attributes` ar
            ON ar.articleID = pci.article_id
         WHERE pcc.category_key LIKE ? AND pci.shop_id = ? AND pci.stream = ?';
        $whereParams = [
            $parent . '/%',
            $shopId,
            $streamName,
        ];

        $this->db->expects($this->once())
            ->method('fetchPairs')
            ->with($sql, $whereParams)
            ->willReturn($rows);

        $this->categoryResolver->expects($this->once())
            ->method('generateTree')
            ->with($rows, $parent)
            ->willReturn([
                '/Kleidung/Hosen' => [
                    'name' => 'Hosen',
                    'children' => [],
                    'categoryId' => '/Kleidung/Hosen',
                    'leaf' => true,
                ],
                '/Kleidung/Mantel' => [
                    'name' => 'Mantel',
                    'children' => [],
                    'categoryId' => '/Kleidung/Mantel',
                    'leaf' => true,
                ],
            ]);

        $this->randomStringGenerator->expects($this->at(0))
            ->method('generate')
            ->with(sprintf('shopId%s~stream~%s~%s', $shopId, $streamName, '/Kleidung/Hosen'))
            ->willReturn(sprintf('shopId%s~stream~%s~%s%s', $shopId, $streamName, '/Kleidung/Hosen', '1049'));

        $this->randomStringGenerator->expects($this->at(1))
            ->method('generate')
            ->with(sprintf('shopId%s~stream~%s~%s', $shopId, $streamName, '/Kleidung/Mantel'))
            ->willReturn(sprintf('shopId%s~stream~%s~%s%s', $shopId, $streamName, '/Kleidung/Mantel', '0981'));

        $this->assertEquals(
            [
                [
                    'id' => 'shopId5~stream~Dress~/Kleidung/Hosen1049',
                    'categoryId' => '/Kleidung/Hosen',
                    'name' => 'Hosen',
                    'leaf' => true,
                    'children' => [],
                    'cls' => 'sc-tree-node',
                    'expanded' => false
                ],
                [
                    'id' => 'shopId5~stream~Dress~/Kleidung/Mantel0981',
                    'categoryId' => '/Kleidung/Mantel',
                    'name' => 'Mantel',
                    'leaf' => true,
                    'children' => [],
                    'cls' => 'sc-tree-node',
                    'expanded' => false
                ],
            ],
            $this->categoryExtractor->getRemoteCategoriesTree($parent, false, false, $shopId, $streamName)
        );
    }
}
