<?php


class CategoryExtractorTest extends \Tests\ShopwarePlugins\Connect\ConnectTestHelper
{
    /**
     * @var \ShopwarePlugins\Connect\Components\CategoryExtractor
     */
    private $categoryExtractor;

    private $configurationGateway;

    private $attributeRepository;

    public function setUp()
    {
        // todo@sb: Improve me with mocks
        Shopware()->Db()->exec("
            INSERT INTO `s_plugin_connect_categories`(`category_key`, `label`, `local_category_id`) VALUES
            ('/Ski-unit','Ski', NULL),
            ('/Kleidung-unit','Kleidung', NULL),
            ('/Kleidung-unit/Hosen-unit','Hosen', 1),
            ('/Kleidung-unit/Hosen-unit/Hosentraeger-unit','Hosentraeger', NULL);
        ");

        $this->configurationGateway = $this->getMockBuilder('\\Shopware\\Connect\\Gateway\\PDO')
            ->disableOriginalConstructor()
            ->getMock();

        $this->attributeRepository = $this->getMockBuilder('\\Shopware\\CustomModels\\Connect\\AttributeRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->categoryExtractor = new \ShopwarePlugins\Connect\Components\CategoryExtractor(
            $this->attributeRepository,
            new \ShopwarePlugins\Connect\Components\CategoryResolver\AutoCategoryResolver(
                Shopware()->Models(),
                Shopware()->Models()->getRepository('Shopware\Models\Category\Category'),
                Shopware()->Models()->getRepository('Shopware\CustomModels\Connect\RemoteCategory')
            ),
            $this->configurationGateway
        );
    }

    public function tearDown()
    {
        Shopware()->Db()->exec("DELETE FROM `s_plugin_connect_categories`");
    }

    public function testExtractImportedCategories()
    {
        $attribute1 = new \Shopware\CustomModels\Connect\Attribute();
        $attribute1->setCategory(array('/Ski' => 'Ski'));
        $attribute1->setStream('Awesome products');

        $attribute2 = new \Shopware\CustomModels\Connect\Attribute();
        $attribute2->setCategory(array(
            '/Kleidung' => 'Kleidung',
            '/Kleidung/Hosen' => 'Hosen',
            '/Kleidung/Hosentraeger' => 'Hosentraeger',
        ));
        $attribute2->setStream('Awesome products');

        $attribute3 = new \Shopware\CustomModels\Connect\Attribute();
        $attribute3->setCategory(array(
            '/Kleidung/Hosentraeger' => 'Hosentraeger',
            '/Kleidung/Nahrung & Getraenke' => 'Nahrung & Getraenke',
            '/Kleidung/Nahrung & Getraenke/Alkoholische Getränke' => 'Alkoholische Getränke',
        ));
        $attribute3->setStream('Awesome products');

        $this->attributeRepository->expects($this->once())
            ->method('findRemoteArticleAttributes')
            ->willReturn(array(
                $attribute1,
                $attribute2,
                $attribute3,
            ));

        $expected = array(
            array(
                'name' => 'Ski',
                'id' => '/Ski',
                'leaf' => true,
                'children' => array(),
            ),
            array(
                'name' => 'Kleidung',
                'id' => '/Kleidung',
                'leaf' => false,
                'children' => array(
                    array(
                        'name' => 'Hosen',
                        'id' => '/Kleidung/Hosen',
                        'leaf' => true,
                        'children' => array(),
                    ),
                    array(
                        'name' => 'Hosentraeger',
                        'id' => '/Kleidung/Hosentraeger',
                        'leaf' => true,
                        'children' => array(),
                    ),
                    array(
                        'name' => 'Nahrung & Getraenke',
                        'id' => '/Kleidung/Nahrung & Getraenke',
                        'leaf' => false,
                        'children' => array(
                            array(
                                'name' => 'Alkoholische Getränke',
                                'id' => '/Kleidung/Nahrung & Getraenke/Alkoholische Getränke',
                                'leaf' => true,
                                'children' => array(),
                            ),
                        ),
                    )
                ),
            ),
        );

        $result = $this->categoryExtractor->extractImportedCategories();
        $this->assertTrue(is_array($result), 'Extracted categories must be array');
        $this->assertEquals($expected, $result);
    }

    public function testGetShopNamesAsCategoriesTree()
    {
        $this->configurationGateway->expects($this->once())
            ->method('getConnectedShopIds')
            ->willReturn(array(1, 2, 3,));

        $this->configurationGateway->expects($this->at(1))
            ->method('getShopConfiguration')
            ->with(1)
            ->willReturn(
                new \Shopware\Connect\Struct\ShopConfiguration(array(
                    'displayName' => 'Shop 1'
                ))
            );

        $this->configurationGateway->expects($this->at(2))
            ->method('getShopConfiguration')
            ->with(2)
            ->willReturn(
                new \Shopware\Connect\Struct\ShopConfiguration(array(
                    'displayName' => 'Shop 2'
                ))
            );

        $this->configurationGateway->expects($this->at(3))
            ->method('getShopConfiguration')
            ->with(3)
            ->willReturn(
                new \Shopware\Connect\Struct\ShopConfiguration(array(
                    'displayName' => 'Shop 3'
                ))
            );

        $expected = array(
            array(
                'id' => 1,
                'name' => 'Shop 1',
                'leaf' => false,
                'children' => array(),
            ),
            array(
                'id' => 2,
                'name' => 'Shop 2',
                'leaf' => false,
                'children' => array(),
            ),
            array(
                'id' => 3,
                'name' => 'Shop 3',
                'leaf' => false,
                'children' => array(),
            ),
        );
        $result = $this->categoryExtractor->getMainNodes();
        $this->assertEquals($expected, $result);
    }

    public function testGetTreeWithoutParent()
    {
        $expected = array(
            array(
                'id' => '/Ski-unit',
                'name' => 'Ski',
                'leaf' => true,
                'children' => array(),
            ),
            array(
                'id' => '/Kleidung-unit',
                'name' => 'Kleidung',
                'leaf' => false,
                'children' => array(),
            ),
        );
        $result = $this->categoryExtractor->getRemoteCategoriesTree();
        $this->assertEquals($expected, $result);
    }

    public function testGetTreeWithoutParentAndIncludeChildren()
    {
        $expected = array(
            array(
                'id' => '/Ski-unit',
                'name' => 'Ski',
                'leaf' => true,
                'children' => array(),
            ),
            array(
                'id' => '/Kleidung-unit',
                'name' => 'Kleidung',
                'leaf' => false,
                'children' => array(
                    array(
                        'id' => '/Kleidung-unit/Hosen-unit',
                        'name' => 'Hosen',
                        'leaf' => false,
                        'children' => array(
                            array(
                                'id' => '/Kleidung-unit/Hosen-unit/Hosentraeger-unit',
                                'name' => 'Hosentraeger',
                                'leaf' => true,
                                'children' => array(),
                            ),
                        ),
                    ),
                ),
            ),
        );
        $parent = null;
        $includeChildren = true;
        $result = $this->categoryExtractor->getRemoteCategoriesTree($parent, $includeChildren);
        $this->assertEquals($expected, $result);
    }

    public function testGetTreeWithoutParentAndExcludeMapped()
    {
        $expected = array(
            array(
                'id' => '/Ski-unit',
                'name' => 'Ski',
                'leaf' => true,
                'children' => array(),
            ),
            array(
                'id' => '/Kleidung-unit',
                'name' => 'Kleidung',
                'leaf' => true,
                'children' => array(),
            ),
        );
        $parent = null;
        $includeChildren = true;
        $excludeMapped = true;
        $result = $this->categoryExtractor->getRemoteCategoriesTree($parent, $includeChildren, $excludeMapped);
        $this->assertEquals($expected, $result);
    }

    public function testGetTreeWithParent()
    {
        $expected = array(
            array(
                'id' => '/Kleidung-unit/Hosen-unit',
                'name' => 'Hosen',
                'leaf' => false,
                'children' => array(),
            ),
        );
        $parent = '/Kleidung-unit';
        $result = $this->categoryExtractor->getRemoteCategoriesTree($parent);
        $this->assertEquals($expected, $result);
    }

    public function testGetTreeWithParentAndIncludeChildren()
    {
        $expected = array(
            array(
                'id' => '/Kleidung-unit/Hosen-unit',
                'name' => 'Hosen',
                'leaf' => false,
                'children' => array(
                    array(
                        'id' => '/Kleidung-unit/Hosen-unit/Hosentraeger-unit',
                        'name' => 'Hosentraeger',
                        'leaf' => true,
                        'children' => array(),
                    ),
                ),
            ),
        );
        $parent = '/Kleidung-unit';
        $includeChildren = true;
        $result = $this->categoryExtractor->getRemoteCategoriesTree($parent, $includeChildren);
        $this->assertEquals($expected, $result);
    }

    public function testGetTreeWithParentAndExcludeMapped()
    {
        $parent = '/Kleidung-unit';
        $includeChildren = true;
        $excludeMapped = true;
        $result = $this->categoryExtractor->getRemoteCategoriesTree($parent, $includeChildren, $excludeMapped);
        $this->assertEmpty($result);
    }

    public function testExtractByShopId()
    {
        $product = $this->getProduct();
        $product->categories = array(
            '/Ski-unit' => 'Ski',
            '/Kleidung-unit' => 'Kleidung',
            '/Kleidung-unit/Hosen-unit' => 'Hosen',
            '/Kleidung-unit/Hosen-unit/Hosentraeger-unit' => 'Hosentraeger',

        );

        $this->getProductToShop()->insertOrUpdate($product);

        $expected = array(
            array(
                'id' => '/Ski-unit',
                'name' => 'Ski',
                'leaf' => true,
                'children' => array(),
            ),
            array(
                'id' => '/Kleidung-unit',
                'name' => 'Kleidung',
                'leaf' => false,
                'children' => array(
                ),
            ),
        );

        $result = $this->categoryExtractor->extractByShopId(3);
        $this->assertEquals($expected, $result);
    }

    public function testExtractByShopIdAndIncludeChildren()
    {
        $product = $this->getProduct();
        $product->categories = array(
            '/Ski-unit' => 'Ski',
            '/Kleidung-unit' => 'Kleidung',
            '/Kleidung-unit/Hosen-unit' => 'Hosen',
            '/Kleidung-unit/Hosen-unit/Hosentraeger-unit' => 'Hosentraeger',

        );

        $this->getProductToShop()->insertOrUpdate($product);

        $expected = array(
            array(
                'id' => '/Ski-unit',
                'name' => 'Ski',
                'leaf' => true,
                'children' => array(),
            ),
            array(
                'id' => '/Kleidung-unit',
                'name' => 'Kleidung',
                'leaf' => false,
                'children' => array(
                    array(
                        'id' => '/Kleidung-unit/Hosen-unit',
                        'name' => 'Hosen',
                        'leaf' => false,
                        'children' => array(
                            array(
                                'id' => '/Kleidung-unit/Hosen-unit/Hosentraeger-unit',
                                'name' => 'Hosentraeger',
                                'leaf' => true,
                                'children' => array(),
                            ),
                        ),
                    ),
                ),
            ),
        );

        $result = $this->categoryExtractor->extractByShopId(3, $includeChildren = true);
        $this->assertEquals($expected, $result);
    }

    public function testGetStreamByShopId()
    {
        $shopId = 1;
        for ($i=0; $i < 3; $i++) {
            $product = $this->getProduct();
            $product->shopId = $shopId;
            $product->stream = 'Awesome products';

            $this->getProductToShop()->insertOrUpdate($product);
        }

        for ($i=0; $i < 3; $i++) {
            $product = $this->getProduct();
            $product->shopId = $shopId;
            $product->stream = 'Mobile devices';

            $this->getProductToShop()->insertOrUpdate($product);
        }

        $streams = $this->categoryExtractor->getStreamsByShopId($shopId);
        $expected = array(
            array(
                'id' => '1_stream_Awesome products',
                'name' => 'Awesome products',
                'leaf' => false,
                'children' => array(),
            ),
            array(
                'id' => '1_stream_Mobile devices',
                'name' => 'Mobile devices',
                'leaf' => false,
                'children' => array(),
            ),
        );

        $this->assertEquals($expected, $streams);
    }

    public function testGetRemoteCategoriesTreeByStream()
    {
        $shopId = 3;
        $stream = 'Media devices';
        for ($i=0; $i < 5; $i++) {
            $product = $this->getProduct();
            $product->shopId = $shopId;
            $product->stream = $stream;
            $product->categories = array(
                '/Ski-unit' => 'Ski',
                '/Kleidung-unit' => 'Kleidung',
                '/Kleidung-unit/Hosen-unit' => 'Hosen',
                '/Kleidung-unit/Hosen-unit/Hosentraeger-unit' => 'Hosentraeger',

            );

            $this->getProductToShop()->insertOrUpdate($product);
        }

        $expected = array(
            array(
                'id' => '/Ski-unit',
                'name' => 'Ski',
                'leaf' => true,
                'children' => array(),
            ),
            array(
                'id' => '/Kleidung-unit',
                'name' => 'Kleidung',
                'leaf' => false,
                'children' => array(),
            ),
        );

        $remoteCategories = $this->categoryExtractor->getRemoteCategoriesTreeByStream($stream, $shopId);
        $this->assertEquals($expected, $remoteCategories);
    }
}
 