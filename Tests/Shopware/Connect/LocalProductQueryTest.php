<?php

namespace Tests\ShopwarePlugins\Connect;

use Shopware\Connect\Struct\PriceRange;
use Shopware\Connect\Struct\Product;
use Shopware\Connect\Struct\Translation;
use ShopwarePlugins\Connect\Components\Config;
use ShopwarePlugins\Connect\Components\Marketplace\MarketplaceGateway;
use ShopwarePlugins\Connect\Components\ProductQuery;
use ShopwarePlugins\Connect\Components\ProductQuery\LocalProductQuery;
use Shopware\Bundle\StoreFrontBundle\Struct\Media;
use Shopware\Models\Property;

class LocalProductQueryTest extends ConnectTestHelper
{
    protected $localProductQuery;

    protected $productTranslator;

    protected $mediaService;

    private $translations;

    protected $localMediaService;

    protected $contextService;

    /** @var \Shopware\Models\Article\Article $article */
    private $article;

    private $db;
    private $manager;

    protected $productContext;

    public function setUp()
    {
        parent::setUp();

        $this->db = Shopware()->Db();
        $this->manager = Shopware()->Models();
        $this->createArticle();

        $this->translations = array(
            'en' => new Translation(
                array(
                    'title' => 'Glas -Teetasse 0,25l EN',
                    'shortDescription' => 'shopware Connect local product short description EN',
                    'longDescription' => 'shopware Connect local product long description EN',
                    'url' => $this->getProductBaseUrl() . '22/shId/2'
                )
            ),
            'nl' => new Translation(
                array(
                    'title' => 'Glas -Teetasse 0,25l NL',
                    'shortDescription' => 'shopware Connect local product short description NL',
                    'longDescription' => 'shopware Connect local product long description NL',
                    'url' => $this->getProductBaseUrl() . '22/shId/176'
                )
            ),
        );

        $this->productTranslator = $this->getMockBuilder('\\ShopwarePlugins\\Connect\\Components\\Translations\\ProductTranslator')
            ->disableOriginalConstructor()
            ->getMock();

        $this->mediaService = $this->getMockBuilder('\\Shopware\\Bundle\\MediaBundle\\MediaService')
            ->disableOriginalConstructor()
            ->getMock();

        $this->mediaService->expects($this->any())
            ->method('getUrl')
            ->with('/media/image/tea_pavilion.jpg')
            ->willReturn('http://myshop/media/image/2e/4f/tea_pavilion.jpg');

        $this->productTranslator->expects($this->any())
            ->method('translate')
            ->willReturn($this->translations);

        $this->productTranslator->expects($this->any())
            ->method('translateConfiguratorGroup')
            ->willReturn($this->translations);

        $this->productTranslator->expects($this->any())
            ->method('translateConfiguratorOption')
            ->willReturn($this->translations);		

        $this->localMediaService = $this->getMockBuilder('\\ShopwarePlugins\\Connect\\Components\\MediaService\\LocalMediaService')
            ->disableOriginalConstructor()
            ->getMock();

        $this->contextService = $this->getMockBuilder('\\Shopware\\Bundle\\StoreFrontBundle\\Service\\Core\\ContextService')
            ->disableOriginalConstructor()
            ->getMock();

        $this->productContext = $this->getMockBuilder('\\Shopware\\Bundle\\StoreFrontBundle\\Struct\\ProductContext')
            ->disableOriginalConstructor()
            ->getMock();
        $this->contextService->expects($this->any())
            ->method('createShopContext')
            ->willReturn($this->productContext);
    }

    public function getLocalProductQuery()
    {
        if (!$this->localProductQuery) {
            /** @var \ShopwarePlugins\Connect\Components\Config $configComponent */
            $configComponent = new Config(Shopware()->Models());

            $this->localProductQuery = new LocalProductQuery(
                Shopware()->Models(),
                $this->getProductBaseUrl(),
                $configComponent,
                new MarketplaceGateway(Shopware()->Models()),
                $this->productTranslator,
                $this->contextService,
                $this->localMediaService,
                $this->mediaService
            );
        }
        return $this->localProductQuery;
    }

    public function getProductBaseUrl()
    {
        if (!Shopware()->Front()->Router()) {
            return null;
        }

        return Shopware()->Front()->Router()->assemble(array(
            'module' => 'frontend',
            'controller' => 'connect_product_gateway',
            'action' => 'product',
            'id' => '',
            'fullPath' => true
        ));
    }

    public function testGetUrlForProduct()
    {
        $expectedUrl = $this->getProductBaseUrl() . '1091';
        $this->assertEquals($expectedUrl, $this->getLocalProductQuery()->getUrlForProduct(1091));
    }

    public function testGetUrlForProductWithShopId()
    {
        $expectedUrl = $this->getProductBaseUrl() . '1091/shId/3';
        $this->assertEquals($expectedUrl, $this->getLocalProductQuery()->getUrlForProduct(1091, 3));
    }

    public function testGetConnectProduct()
    {
        $row = array (
            'sku' => 'SW10005',
            'sourceId' => '22',
            'ean' => NULL,
            'title' => 'Glas -Teetasse 0,25l',
            'shortDescription' => 'Almus Emitto Bos sicut hae Amplitudo rixa ortus retribuo Vicarius an nam capitagium medius.',
            'vendor' =>  array(
                'name' => 'Teapavilion',
                'description' => 'Teapavilion description',
                'logo_url' => 'tea_pavilion.jpg',
                'url' => 'http://teapavilion.com',
                'page_title' => 'Teapavilion title',
            ),
            'vat' => '0.190000',
            'availability' => 3445,
            'price' => 10.924369747899,
            'purchasePrice' => 0,
            'longDescription' => '<p>Reficio congratulor simplex Ile familia mire hae Prosequor in pro St quae Muto,, St Texo aer Cornu ferox lex inconsiderate propitius, animus ops nos haero vietus Subdo qui Gemo ipse somniculosus. Non Apertio ops, per Repere torpeo penintentiarius Synagoga res mala caelestis praestigiator. Ineo via consectatio Gemitus sui domus ludio is vulgariter, hic ut legens nox Falx nos cui vaco insudo tero, tollo valde emo. deprecativus fio redigo probabiliter pacificus sem Nequequam, suppliciter dis Te summisse Consuesco cur Desolo sis insolesco expeditus pes Curo aut Crocotula Trimodus. Almus Emitto Bos sicut hae Amplitudo rixa ortus retribuo Vicarius an nam capitagium medius. Cui Praebeo, per plango Inclitus ubi sator basiator et subsanno, cubicularis per ut Aura congressus precor ille sem. aro quid ius Praedatio vitupero Tractare nos premo procurator. Ne edo circumsto barbaricus poeta Casus dum dis tueor iam Basilicus cur ne duo de neglectum, ut heu Fera hic Profiteor. Ius Perpetuus stilla co.</p>',
            'fixedPrice' => null,
            'deliveryWorkDays' => null,
            'shipping' => null,
            'translations' => [],
            'attributes' => [
                'unit' => null,
                'quantity' => null,
                'ref_quantity' => null,
            ],
        );

        $productMedia = [];
        for ($i = 1; $i < 12; $i++) {
            $media = new Media();
            $media->setFile(sprintf('http://myshop/media/image/2e/4f/tea_pavilion_product_image%s.jpg', $i));
            $productMedia[] = $media;
        }

        $variantMedia = [];
        for ($i = 1; $i < 12; $i++) {
            $media = new Media();
            $media->setFile(sprintf('http://myshop/media/image/2e/4f/tea_pavilion_variant_image%s.jpg', $i));
            $variantMedia[] = $media;
        }

        $this->localMediaService->expects($this->once())
            ->method('getProductMediaList')
            ->with($this->anything(), $this->productContext)
            ->willReturn([$row['sku'] => $productMedia]);

        $this->localMediaService->expects($this->once())
            ->method('getVariantMediaList')
            ->with($this->anything(), $this->productContext)
            ->willReturn([$row['sku'] => $variantMedia]);

        $expectedProduct = new Product($row);
        $expectedProduct->vendor['logo_url'] = 'http://myshop/media/image/2e/4f/tea_pavilion.jpg';
        $expectedProduct->url = $this->getProductBaseUrl() . '22';
        $expectedProduct->attributes = array(
            'quantity' => NULL,
            'ref_quantity' => NULL,
        );
        $expectedProduct->translations = $this->translations;
        $expectedProduct->priceRanges = [
            new PriceRange([
                'customerGroupKey' => 'EK',
                'from' => 1,
                'to' => 5,
                'price' => 123.99,
            ]),
            new PriceRange([
                'customerGroupKey' => 'EK',
                'from' => 6,
                'to' => PriceRange::ANY,
                'price' => 113.99,
            ]),
        ];

        $expectedProduct->properties = [
            new \Shopware\Connect\Struct\Property([
                'groupName' => 'Nike',
                'groupPosition' => 3,
                'comparable' => false,
                'sortMode' => 2,
                'option' => 'color',
                'filterable' => false,
                'value' => 'green',
                'valuePosition' => 0,
            ]),
            new \Shopware\Connect\Struct\Property([
                'groupName' => 'Nike',
                'groupPosition' => 3,
                'comparable' => false,
                'sortMode' => 2,
                'option' => 'size',
                'filterable' => false,
                'value' => '2xl',
                'valuePosition' => 0,
            ]),
            new \Shopware\Connect\Struct\Property([
                'groupName' => 'Nike',
                'groupPosition' => 3,
                'comparable' => false,
                'sortMode' => 2,
                'option' => 'size',
                'filterable' => false,
                'value' => '3xl',
                'valuePosition' => 0,
            ]),
        ];

		$expectedProduct->images = array(
            'http://myshop/media/image/2e/4f/tea_pavilion_product_image1.jpg',
            'http://myshop/media/image/2e/4f/tea_pavilion_product_image2.jpg',
            'http://myshop/media/image/2e/4f/tea_pavilion_product_image3.jpg',
            'http://myshop/media/image/2e/4f/tea_pavilion_product_image4.jpg',
            'http://myshop/media/image/2e/4f/tea_pavilion_product_image5.jpg',
            'http://myshop/media/image/2e/4f/tea_pavilion_product_image6.jpg',
            'http://myshop/media/image/2e/4f/tea_pavilion_product_image7.jpg',
            'http://myshop/media/image/2e/4f/tea_pavilion_product_image8.jpg',
            'http://myshop/media/image/2e/4f/tea_pavilion_product_image9.jpg',
            'http://myshop/media/image/2e/4f/tea_pavilion_product_image10.jpg',
        );
        $expectedProduct->variantImages = array(
            'http://myshop/media/image/2e/4f/tea_pavilion_variant_image1.jpg',
            'http://myshop/media/image/2e/4f/tea_pavilion_variant_image2.jpg',
            'http://myshop/media/image/2e/4f/tea_pavilion_variant_image3.jpg',
            'http://myshop/media/image/2e/4f/tea_pavilion_variant_image4.jpg',
            'http://myshop/media/image/2e/4f/tea_pavilion_variant_image5.jpg',
        );

        $row['vendorName'] = $row['vendor']['name'];
        $row['vendorLink'] = $row['vendor']['url'];
        $row['vendorImage'] = $row['vendor']['logo_url'];
        $row['vendorDescription'] = $row['vendor']['description'];
        $row['vendorMetaTitle'] = $row['vendor']['page_title'];
        unset($row['vendor']);
        $row['category'] = '';
        $row['weight'] = null;
        $row['unit'] = null;
        $row['localId'] = $this->article->getId();
        $row['detailId'] = $this->article->getMainDetail()->getId();

        $this->assertEquals($expectedProduct, $this->getLocalProductQuery()->getConnectProduct($row));
    }

    private function createArticle()
    {
        $group = $this->manager->getRepository(Property\Group::class)->findOneBy(
            ['name' => 'Nike']
        );

        if (!$group) {
            $group = new Property\Group();
            $group->setName('Nike');
            $group->setPosition(3);
            $group->setSortMode(2);
            $group->setComparable(0);
            $this->manager->persist($group);
            $this->manager->flush();
        }

        $minimalTestArticle = array(
            'name' => 'Glas -Teetasse 0,25l',
            'active' => true,
            'tax' => 19,
            'supplier' => 'Teapavilion',
            'mainDetail' => array(
                'number' => '9898' . rand(1, 99999),
            ),
            'filterGroupId' => $group->getId(),
            'propertyValues' => array(
                array(
                    'option' => array(
                        'name' => 'color',
                    ),
                    'value' => 'green'
                ),
                array(
                    'option' => array(
                        'name' => 'size',
                    ),
                    'value' => '2xl'
                ),
                array(
                    'option' => array(
                        'name' => 'size',
                    ),
                    'value' => '3xl'
                )
            )
        );

        $articleResource = \Shopware\Components\Api\Manager::getResource('article');
        /** @var \Shopware\Models\Article\Article $article */
        $this->article = $articleResource->create($minimalTestArticle);

        $this->db->insert(
            's_articles_prices',
            array(
                'pricegroup' => 'EK',
                'from' => 1,
                'to' => 5,
                'price' => 123.99,
                'articleID' => $this->article->getId(),
                'articledetailsID' => $this->article->getMainDetail()->getId(),
                'pseudoprice' => 0
            )
        );

        $this->db->insert(
            's_articles_prices',
            array(
                'pricegroup' => 'EK',
                'from' => 6,
                'to' => 'beliebig',
                'price' => 113.99,
                'articleID' => $this->article->getId(),
                'articledetailsID' => $this->article->getMainDetail()->getId(),
                'pseudoprice' => 0
            )
        );
    }


    public function tearDown()
    {
        if (!$this->article) {
            return;
        }

        $articleId = $this->article->getId();
        $this->db->exec("DELETE FROM s_articles WHERE id = $articleId");
        $this->db->exec('DELETE FROM s_articles_details WHERE ordernumber LIKE "9898%"');
        $this->db->exec("DELETE FROM s_articles_prices WHERE articleID = $articleId");
    }
}
