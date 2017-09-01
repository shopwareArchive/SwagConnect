<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\ShopwarePlugins\Connect\Component;

use ShopwarePlugins\Connect\Components\RandomStringGenerator;

class CategoryExtractorTest extends \Tests\ShopwarePlugins\Connect\ConnectTestHelper
{
    const RANDOM_STRING = '9999abcxyz';
    /**
     * @var \ShopwarePlugins\Connect\Components\CategoryExtractor
     */
    private $categoryExtractor;

    private $configurationGateway;

    private $attributeRepository;

    private $db;
    private $em;
    private $articleA;
    private $articleB;

    private function createArticleA()
    {
        $minimalTestArticle = [
            'name' => 'TurnschuhA',
            'active' => true,
            'tax' => 19,
            'supplier' => 'Turnschuh Inc.',
            'categories' => [
                ['id' => 15],
            ],
            'mainDetail' => [
                'number' => '9898',
            ],
        ];

        $articleResource = \Shopware\Components\Api\Manager::getResource('article');
        /** @var \Shopware\Models\Article\Article $article */
        $this->articleA = $articleResource->create($minimalTestArticle);
    }

    private function createArticleB()
    {
        $minimalTestArticle = [
            'name' => 'TurnschuhB',
            'active' => true,
            'tax' => 19,
            'supplier' => 'Turnschuh Inc.',
            'categories' => [
                ['id' => 15],
            ],
            'mainDetail' => [
                'number' => '9897',
            ],
        ];

        $articleResource = \Shopware\Components\Api\Manager::getResource('article');
        /** @var \Shopware\Models\Article\Article $article */
        $this->articleB = $articleResource->create($minimalTestArticle);
    }

    public function setUp()
    {
        parent::setUp();

        $this->db = Shopware()->Db();
        $this->em = Shopware()->Models();

        $this->createArticleA();
        $this->createArticleB();

        /** @var \Shopware\Models\Article\Detail $detailA */
        $detailA = $this->articleA->getMainDetail();
        $detailB = $this->articleB->getMainDetail();
        $this->db->exec(
            'INSERT INTO s_plugin_connect_items (article_id, article_detail_id, shop_id, source_id, category) VALUES
                (' . $this->articleA->getId() . ',' . $detailA->getId() . ',1,' . $detailA->getNumber() . ', "/bücher"),
                (' . $this->articleB->getId() . ',' . $detailB->getId() . ',1,' . $detailB->getNumber() . ', "/bücher")
            '
        );

        $categories = [
            ['category_key' => '/Ski-unit', 'label' => 'Ski'],
            ['category_key' => '/Kleidung-unit', 'label' => 'Kleidung'],
            ['category_key' => '/Kleidung-unit/Hosen-unit', 'label' => 'Hosen', 'local_category_id' => 1],
            ['category_key' => '/Kleidung-unit/Hosen-unit/Hosentraeger-unit', 'label' => 'Hosentraeger'],
        ];

        //inserts a category with map product
        $this->db->exec("
            INSERT INTO `s_plugin_connect_categories`(`category_key`, `label`, `local_category_id`) VALUES
            ('/Kleidung-unit/SchuheA-unit','SchuheA', NULL)
        ");
        $this->db->exec("
            INSERT INTO `s_plugin_connect_categories`(`category_key`, `label`, `local_category_id`) VALUES
            ('/Kleidung-unit/SchuheA-unit/SchuheB-unit','SchuheB', NULL)
        ");
        $this->db->insert(
            's_plugin_connect_product_to_categories',
            [
                'connect_category_id' => $this->db->lastInsertId(),
                'articleID' => $this->articleB->getId(),
            ]
        );
        $this->db->update(
            's_articles_attributes',
            ['connect_mapped_category' => 1],
            ['articleID=' . $this->articleB->getId()]
        );

        // todo@sb: Improve me with mocks
        foreach ($categories as $category) {
            $this->db->insert('s_plugin_connect_categories', $category);
            $categoryId = $this->db->lastInsertId();

            $this->db->insert('s_plugin_connect_categories_to_local_categories', ['local_category_id' => 1, 'remote_category_id' => $categoryId]);

            $this->db->insert('s_plugin_connect_product_to_categories', [
                'connect_category_id' => $categoryId,
                'articleID' => $this->articleA->getId(),
            ]);
        }

        $this->configurationGateway = $this->getMockBuilder('\\Shopware\\Connect\\Gateway\\PDO')
            ->disableOriginalConstructor()
            ->getMock();

        $this->attributeRepository = $this->getMockBuilder('\\Shopware\\CustomModels\\Connect\\AttributeRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $randomStringGenerator = $this->getMockBuilder(RandomStringGenerator::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->categoryExtractor = new \ShopwarePlugins\Connect\Components\CategoryExtractor(
            $this->attributeRepository,
            new \ShopwarePlugins\Connect\Components\CategoryResolver\AutoCategoryResolver(
                $this->em,
                $this->em->getRepository('Shopware\Models\Category\Category'),
                $this->em->getRepository('Shopware\CustomModels\Connect\RemoteCategory'),
                new \ShopwarePlugins\Connect\Components\Config($this->em)
            ),
            $this->configurationGateway,
            $randomStringGenerator,
            $this->db
        );

        $randomStringGenerator->expects($this->any())
            ->method('generate')
            ->willReturn(self::RANDOM_STRING);
    }

    public function tearDown()
    {
        $this->db->exec('DELETE FROM `s_plugin_connect_categories_to_local_categories`');
        $this->db->exec('DELETE FROM `s_plugin_connect_categories`');
        $this->db->exec('DELETE FROM `s_plugin_connect_product_to_categories`');
        $this->db->exec('DELETE FROM sw_connect_change WHERE c_entity_id LIKE "9898%"');
        $this->db->exec('DELETE FROM sw_connect_change WHERE c_entity_id LIKE "9897%"');
        $this->db->exec('DELETE FROM s_articles WHERE name = "Turnschuh"');
        $this->db->exec('DELETE FROM s_articles_details WHERE ordernumber LIKE "9898%"');
        $this->db->exec('DELETE FROM s_plugin_connect_items WHERE source_id LIKE "9898%"');
        $this->db->exec('DELETE FROM s_articles_details WHERE ordernumber LIKE "9897%"');
        $this->db->exec('DELETE FROM s_plugin_connect_items WHERE source_id LIKE "9897%"');
    }

    public function testExtractImportedCategories()
    {
        $attribute1 = new \Shopware\CustomModels\Connect\Attribute();
        $attribute1->setCategory(['/Ski' => 'Ski']);
        $attribute1->setStream('Awesome products');

        $attribute2 = new \Shopware\CustomModels\Connect\Attribute();
        $attribute2->setCategory([
            '/Kleidung' => 'Kleidung',
            '/Kleidung/Hosen' => 'Hosen',
            '/Kleidung/Hosentraeger' => 'Hosentraeger',
        ]);
        $attribute2->setStream('Awesome products');

        $attribute3 = new \Shopware\CustomModels\Connect\Attribute();
        $attribute3->setCategory([
            '/Kleidung/Hosentraeger' => 'Hosentraeger',
            '/Kleidung/Nahrung & Getraenke' => 'Nahrung & Getraenke',
            '/Kleidung/Nahrung & Getraenke/Alkoholische Getränke' => 'Alkoholische Getränke',
        ]);
        $attribute3->setStream('Awesome products');

        $this->attributeRepository->expects($this->once())
            ->method('findRemoteArticleAttributes')
            ->willReturn([
                $attribute1,
                $attribute2,
                $attribute3,
            ]);

        $expected = [
            [
                'name' => 'Ski',
                'id' => self::RANDOM_STRING,
                'categoryId' => '/Ski',
                'leaf' => true,
                'children' => [],
                'cls' => 'sc-tree-node',
                'expanded' => false,
            ],
            [
                'name' => 'Kleidung',
                'id' => self::RANDOM_STRING,
                'categoryId' => '/Kleidung',
                'leaf' => false,
                'children' => [
                    [
                        'name' => 'Hosen',
                        'id' => self::RANDOM_STRING,
                        'categoryId' => '/Kleidung/Hosen',
                        'leaf' => true,
                        'children' => [],
                        'cls' => 'sc-tree-node',
                        'expanded' => false,
                    ],
                    [
                        'name' => 'Hosentraeger',
                        'id' => self::RANDOM_STRING,
                        'categoryId' => '/Kleidung/Hosentraeger',
                        'leaf' => true,
                        'children' => [],
                        'cls' => 'sc-tree-node',
                        'expanded' => false,
                    ],
                    [
                        'name' => 'Nahrung & Getraenke',
                        'id' => self::RANDOM_STRING,
                        'categoryId' => '/Kleidung/Nahrung & Getraenke',
                        'leaf' => false,
                        'children' => [
                            [
                                'name' => 'Alkoholische Getränke',
                                'id' => self::RANDOM_STRING,
                                'categoryId' => '/Kleidung/Nahrung & Getraenke/Alkoholische Getränke',
                                'leaf' => true,
                                'children' => [],
                                'cls' => 'sc-tree-node',
                                'expanded' => false,
                            ],
                        ],
                        'cls' => 'sc-tree-node',
                        'expanded' => false,
                    ]
                ],
                'cls' => 'sc-tree-node',
                'expanded' => false,
            ],
        ];


        $result = $this->categoryExtractor->extractImportedCategories();
        $this->assertTrue(is_array($result), 'Extracted categories must be array');
        $this->assertEquals($expected, $result);
    }

    public function testGetShopNamesAsCategoriesTree()
    {
        $this->configurationGateway->expects($this->once())
            ->method('getConnectedShopIds')
            ->willReturn([1, 2, 5,]);

        $this->configurationGateway->expects($this->at(1))
            ->method('getShopConfiguration')
            ->with(1)
            ->willReturn(
                new \Shopware\Connect\Struct\ShopConfiguration([
                    'displayName' => 'Shop 1'
                ])
            );

        $expected = [
            [
                'id' => self::RANDOM_STRING,
                'categoryId' => 1,
                'name' => 'Shop 1',
                'leaf' => false,
                'children' => [],
                'iconCls' => 'sc-tree-node-icon',
                'cls' => 'sc-tree-node',
                'expanded' => false,
            ],
        ];
        $result = $this->categoryExtractor->getMainNodes();
        $this->assertEquals($expected, $result);
    }

    public function testGetTreeWithoutParent()
    {
        $expected = [
            [
                'id' => self::RANDOM_STRING,
                'categoryId' => '/Ski-unit',
                'name' => 'Ski',
                'leaf' => true,
                'children' => [],
                'cls' => 'sc-tree-node',
                'expanded' => false
            ],
            [
                'id' => self::RANDOM_STRING,
                'categoryId' => '/Kleidung-unit',
                'name' => 'Kleidung',
                'leaf' => false,
                'children' => [],
                'cls' => 'sc-tree-node',
                'expanded' => false
            ],
        ];
        $result = $this->categoryExtractor->getRemoteCategoriesTree();
        $this->assertEquals($expected, $result);
    }

    public function testGetTreeWithoutParentAndIncludeChildren()
    {
        $expected = [
            [
                'id' => self::RANDOM_STRING,
                'categoryId' => '/Ski-unit',
                'name' => 'Ski',
                'leaf' => true,
                'children' => [],
                'cls' => 'sc-tree-node',
                'expanded' => false
            ],
            [
                'id' => self::RANDOM_STRING,
                'categoryId' => '/Kleidung-unit',
                'name' => 'Kleidung',
                'leaf' => false,
                'children' => [
                    [
                        'id' => self::RANDOM_STRING,
                        'categoryId' => '/Kleidung-unit/Hosen-unit',
                        'name' => 'Hosen',
                        'leaf' => false,
                        'children' => [
                            [
                                'id' => self::RANDOM_STRING,
                                'categoryId' => '/Kleidung-unit/Hosen-unit/Hosentraeger-unit',
                                'name' => 'Hosentraeger',
                                'leaf' => true,
                                'children' => [],
                                'cls' => 'sc-tree-node',
                                'expanded' => false
                            ],
                        ],
                        'cls' => 'sc-tree-node',
                        'expanded' => false
                    ],
                ],
                'cls' => 'sc-tree-node',
                'expanded' => false
            ],
        ];
        $parent = null;
        $includeChildren = true;
        $result = $this->categoryExtractor->getRemoteCategoriesTree($parent, $includeChildren);
        $this->assertEquals($expected, $result);
    }

    public function testGetTreeWithoutParentAndExcludeMapped()
    {
        $expected = [
            [
                'id' => self::RANDOM_STRING,
                'categoryId' => '/Ski-unit',
                'name' => 'Ski',
                'leaf' => true,
                'children' => [],
                'cls' => 'sc-tree-node',
                'expanded' => false
            ],
            [
                'id' => self::RANDOM_STRING,
                'categoryId' => '/Kleidung-unit',
                'name' => 'Kleidung',
                'leaf' => false,
                'children' => [
                    [
                        'id' => self::RANDOM_STRING,
                        'categoryId' => '/Kleidung-unit/Hosen-unit',
                        'name' => 'Hosen',
                        'leaf' => false,
                        'children' => [
                            [
                                'id' => self::RANDOM_STRING,
                                'categoryId' => '/Kleidung-unit/Hosen-unit/Hosentraeger-unit',
                                'name' => 'Hosentraeger',
                                'leaf' => true,
                                'children' => [],
                                'cls' => 'sc-tree-node',
                                'expanded' => false
                            ],
                        ],
                        'cls' => 'sc-tree-node',
                        'expanded' => false
                    ],
                ],
                'cls' => 'sc-tree-node',
                'expanded' => false
            ],
        ];
        $parent = null;
        $includeChildren = true;
        $excludeMapped = true;
        $result = $this->categoryExtractor->getRemoteCategoriesTree($parent, $includeChildren, $excludeMapped);
        $this->assertEquals($expected, $result);
    }

    public function testGetTreeWithParent()
    {
        $expected = [
            [
                'id' => self::RANDOM_STRING,
                'categoryId' => '/Kleidung-unit/Hosen-unit',
                'name' => 'Hosen',
                'leaf' => false,
                'children' => [],
                'cls' => 'sc-tree-node',
                'expanded' => false
            ],
        ];
        $parent = '/Kleidung-unit';
        $result = $this->categoryExtractor->getRemoteCategoriesTree($parent);
        $this->assertEquals($expected, $result);
    }

    public function testGetTreeWithParentAndIncludeChildren()
    {
        $expected = [
            [
                'id' => self::RANDOM_STRING,
                'categoryId' => '/Kleidung-unit/Hosen-unit',
                'name' => 'Hosen',
                'leaf' => false,
                'children' => [
                    [
                        'id' => self::RANDOM_STRING,
                        'categoryId' => '/Kleidung-unit/Hosen-unit/Hosentraeger-unit',
                        'name' => 'Hosentraeger',
                        'leaf' => true,
                        'children' => [],
                        'cls' => 'sc-tree-node',
                        'expanded' => false
                    ],
                ],
                'cls' => 'sc-tree-node',
                'expanded' => false
            ],
        ];
        $parent = '/Kleidung-unit';
        $includeChildren = true;
        $result = $this->categoryExtractor->getRemoteCategoriesTree($parent, $includeChildren);
        $this->assertEquals($expected, $result);
    }

    public function testGetTreeWithParentAndExcludeMapped()
    {
        $parent = '/Kleidung-unit/SchuheA-unit';
        $includeChildren = true;
        $excludeMapped = true;
        $result = $this->categoryExtractor->getRemoteCategoriesTree($parent, $includeChildren, $excludeMapped);
        $this->assertEmpty($result);
    }

    public function testExtractByShopId()
    {
        $product = $this->getProduct();
        $product->categories = [
            '/Ski-unit' => 'Ski',
            '/Kleidung-unit' => 'Kleidung',
            '/Kleidung-unit/Hosen-unit' => 'Hosen',
            '/Kleidung-unit/Hosen-unit/Hosentraeger-unit' => 'Hosentraeger',

        ];

        $this->getProductToShop()->insertOrUpdate($product);

        $expected = [
            [
                'id' => self::RANDOM_STRING,
                'categoryId' => '/Ski-unit',
                'name' => 'Ski',
                'leaf' => true,
                'children' => [],
                'cls' => 'sc-tree-node',
                'expanded' => false
            ],
            [
                'id' => self::RANDOM_STRING,
                'categoryId' => '/Kleidung-unit',
                'name' => 'Kleidung',
                'leaf' => false,
                'children' => [],
                'cls' => 'sc-tree-node',
                'expanded' => false
            ],
        ];

        $result = $this->categoryExtractor->extractByShopId(3);
        $this->assertEquals($expected, $result);
    }

    public function testExtractByShopIdAndIncludeChildren()
    {
        $product = $this->getProduct();
        $product->categories = [
            '/Ski-unit' => 'Ski',
            '/Kleidung-unit' => 'Kleidung',
            '/Kleidung-unit/Hosen-unit' => 'Hosen',
            '/Kleidung-unit/Hosen-unit/Hosentraeger-unit' => 'Hosentraeger',

        ];

        $this->getProductToShop()->insertOrUpdate($product);

        $expected = [
            [
                'id' => self::RANDOM_STRING,
                'categoryId' => '/Ski-unit',
                'name' => 'Ski',
                'leaf' => true,
                'children' => [],
                'cls' => 'sc-tree-node',
                'expanded' => false
            ],
            [
                'id' => self::RANDOM_STRING,
                'categoryId' => '/Kleidung-unit',
                'name' => 'Kleidung',
                'leaf' => false,
                'children' => [
                    [
                        'id' => self::RANDOM_STRING,
                        'categoryId' => '/Kleidung-unit/Hosen-unit',
                        'name' => 'Hosen',
                        'leaf' => false,
                        'children' => [
                            [
                                'id' => self::RANDOM_STRING,
                                'categoryId' => '/Kleidung-unit/Hosen-unit/Hosentraeger-unit',
                                'name' => 'Hosentraeger',
                                'leaf' => true,
                                'children' => [],
                                'cls' => 'sc-tree-node',
                                'expanded' => false
                            ],
                        ],
                        'cls' => 'sc-tree-node',
                        'expanded' => false
                    ],
                ],
                'cls' => 'sc-tree-node',
                'expanded' => false
            ],
        ];

        $result = $this->categoryExtractor->extractByShopId(3, $includeChildren = true);
        $this->assertEquals($expected, $result);
    }

    public function testGetStreamByShopId()
    {
        $shopId = 1;
        for ($i=0; $i < 3; ++$i) {
            $product = $this->getProduct();
            $product->shopId = $shopId;
            $product->stream = 'Awesome products';

            $this->getProductToShop()->insertOrUpdate($product);
        }

        for ($i=0; $i < 3; ++$i) {
            $product = $this->getProduct();
            $product->shopId = $shopId;
            $product->stream = 'Mobile devices';

            $this->getProductToShop()->insertOrUpdate($product);
        }

        $streams = $this->categoryExtractor->getStreamsByShopId($shopId);
        $expected = [
            [
                'id' => self::RANDOM_STRING,
                'categoryId' => '1_stream_Awesome products',
                'name' => 'Awesome products',
                'leaf' => false,
                'children' => [],
                'iconCls' => 'sprite-product-streams',
                'cls' => 'sc-tree-node',
                'expanded' => false
            ],
            [
                'id' => self::RANDOM_STRING,
                'categoryId' => '1_stream_Mobile devices',
                'name' => 'Mobile devices',
                'leaf' => false,
                'children' => [],
                'iconCls' => 'sprite-product-streams',
                'cls' => 'sc-tree-node',
                'expanded' => false
            ],
        ];

        $this->assertEquals($expected, $streams);
    }

    public function testGetRemoteCategoriesTreeByStream()
    {
        $shopId = 3;
        $stream = 'Media devices';
        for ($i=0; $i < 5; ++$i) {
            $product = $this->getProduct();
            $product->shopId = $shopId;
            $product->stream = $stream;
            $product->categories = [
                '/Ski-unit' => 'Ski',
                '/Kleidung-unit' => 'Kleidung',
                '/Kleidung-unit/Hosen-unit' => 'Hosen',
                '/Kleidung-unit/Hosen-unit/Hosentraeger-unit' => 'Hosentraeger',

            ];

            $this->getProductToShop()->insertOrUpdate($product);
        }

        $expected = [
            [
                'id' => self::RANDOM_STRING,
                'categoryId' => '/Ski-unit',
                'name' => 'Ski',
                'leaf' => true,
                'children' => [],
                'cls' => 'sc-tree-node',
                'expanded' => false
            ],
            [
                'id' => self::RANDOM_STRING,
                'categoryId' => '/Kleidung-unit',
                'name' => 'Kleidung',
                'leaf' => false,
                'children' => [],
                'cls' => 'sc-tree-node',
                'expanded' => false
            ],
        ];

        $remoteCategories = $this->categoryExtractor->getRemoteCategoriesTreeByStream($stream, $shopId);
        $this->assertEquals($expected, $remoteCategories);
    }

    /**
     * Test concat shopId, categoryId and unique number
     */
    public function testConcatShopIdAndCategoryId()
    {
        $shopId = 1;

        $randomStringGenerator = $this->getMockBuilder(RandomStringGenerator::class)
            ->disableOriginalConstructor()
            ->getMock();

        $categoryExtractor = new \ShopwarePlugins\Connect\Components\CategoryExtractor(
            $this->attributeRepository,
            new \ShopwarePlugins\Connect\Components\CategoryResolver\AutoCategoryResolver(
                $this->em,
                $this->em->getRepository('Shopware\Models\Category\Category'),
                $this->em->getRepository('Shopware\CustomModels\Connect\RemoteCategory'),
                new \ShopwarePlugins\Connect\Components\Config($this->em)
            ),
            $this->configurationGateway,
            $randomStringGenerator,
            $this->db
        );

        $argument = sprintf('shopId%s~%s', $shopId, '/Kleidung-unit/Hosen-unit/Hosentraeger-unit');
        $randomStringGenerator->expects($this->once())
            ->method('generate')
            ->with($argument)
            ->willReturn($argument . '1040');

        $parent = '/Kleidung-unit/Hosen-unit';
        $includeChildren = true;
        $result = $categoryExtractor->getRemoteCategoriesTree($parent, $includeChildren, false, $shopId);
        $this->assertEquals(
            [
                [
                    'id' => 'shopId1~/Kleidung-unit/Hosen-unit/Hosentraeger-unit1040',
                    'categoryId' => '/Kleidung-unit/Hosen-unit/Hosentraeger-unit',
                    'name' => 'Hosentraeger',
                    'leaf' => true,
                    'children' => [],
                    'cls' => 'sc-tree-node',
                    'expanded' => false
                ]
            ],
            $result
        );
    }
}
