<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Tests\Integration\Components\ProductQuery;

use Shopware\Connect\Struct\Product;
use ShopwarePlugins\Connect\Components\ConfigFactory;
use ShopwarePlugins\Connect\Components\Gateway\ProductTranslationsGateway\PdoProductTranslationsGateway;
use ShopwarePlugins\Connect\Components\Marketplace\MarketplaceGateway;
use ShopwarePlugins\Connect\Components\MediaService\LocalMediaService;
use ShopwarePlugins\Connect\Components\ProductQuery\LocalProductQuery;
use ShopwarePlugins\Connect\Components\Translations\ProductTranslator;
use ShopwarePlugins\Connect\Tests\DatabaseTestCaseTrait;
use Shopware\Components\Model\ModelManager;

class LocalProductQueryTest extends \PHPUnit_Framework_TestCase
{
    use DatabaseTestCaseTrait;

    /**
     * @var LocalProductQuery
     */
    private $localProductQuery;

    /**
     * @var ModelManager
     */
    private $manager;

    public function setUp()
    {
        $this->manager = Shopware()->Models();
        $config = ConfigFactory::getConfigInstance();

        $this->localProductQuery = new LocalProductQuery(
            $this->manager,
            $this->getProductBaseUrl(),
            $config,
            new MarketplaceGateway($this->manager),
            new ProductTranslator(
                $config,
                new PdoProductTranslationsGateway(Shopware()->Db()),
                $this->manager,
                $this->getProductBaseUrl()
            ),
            Shopware()->Container()->get('shopware_storefront.context_service'),
            new LocalMediaService(
                Shopware()->Container()->get('shopware_storefront.product_media_gateway'),
                Shopware()->Container()->get('shopware_storefront.variant_media_gateway'),
                Shopware()->Container()->get('shopware_storefront.media_service')
            ),
            Shopware()->Container()->get('events'),
            Shopware()->Container()->get('shopware_media.media_service')
        );
    }

    private function applyFixtures()
    {
        $this->importFixtures('Tests/Integration/Components/ProductQuery/Fixtures/ProductQueryFixtures.sql');

        if (method_exists('Shopware\Models\Article\Detail', 'setPurchasePrice')) {
            $purchasePriceField = 'detailPurchasePrice';
        } else {
            $purchasePriceField = 'basePrice';
        }

        Shopware()->Db()->executeQuery(
            "DELETE FROM s_plugin_connect_config WHERE `name` = 'priceFieldForPurchasePriceExport'"
        );
        Shopware()->Db()->executeQuery(
            'INSERT IGNORE INTO s_plugin_connect_config (`name`, `value`, `groupName`)
                  VALUES (?, ?, ?)
                  ON DUPLICATE KEY UPDATE
                  `value` = VALUES(`value`)
              ',
            [
                'priceFieldForPurchasePriceExport',
                $purchasePriceField,
                'export'
            ]
        );
    }

    public function test_get()
    {
        $this->applyFixtures();
        $result = $this->localProductQuery->get([3]);
        $this->assertCount(1, $result);
        /** @var \Shopware\Connect\Struct\Product $product */
        $product = $result[0];

        $this->assertInstanceOf('\Shopware\Connect\Struct\Product', $product);
        $this->assertEquals('Münsterländer Aperitif 16%', $product->title);
        $this->assertEquals(12.56, round($product->price, 2));
        $this->assertEquals(1, $product->minPurchaseQuantity);
        $this->assertEquals('l', $product->attributes[Product::ATTRIBUTE_UNIT]);
        $this->assertEquals('0.7000', $product->attributes[Product::ATTRIBUTE_QUANTITY]);
        $this->assertEquals('1.000', $product->attributes[Product::ATTRIBUTE_REFERENCE_QUANTITY]);
    }

    /**
     * When configurator_set_id column is empty in s_articles is NULL,
     * product must be exported as a article without variants even
     * when it has relations with configurator options
     */
    public function test_export_product_without_configurator_set()
    {
        $this->manager->getConnection()->executeQuery(
            'INSERT INTO s_plugin_connect_items (article_id, article_detail_id, source_id) VALUES (?, ?, ?);',
            [2, 123, '2-123']
        );

        $result = $this->localProductQuery->get(['2-123']);
        $product = reset($result);
        $this->assertNotEmpty($product->variant);

        $this->manager->getConnection()->executeQuery(
            'UPDATE s_articles SET configurator_set_id = NULL WHERE id = 2'
        );

        $result = $this->localProductQuery->get(['2-123']);
        $product = reset($result);
        $this->assertEmpty($product->variant);
    }

    public function test_has_variants()
    {
        $this->assertTrue($this->localProductQuery->hasVariants(2));
    }

    public function testGetConnectProduct()
    {
        $this->manager->getConnection()->executeQuery('INSERT IGNORE INTO s_articles_relationships (articleID, relatedarticle) VALUES (1234, 1)');
        $this->manager->getConnection()->executeQuery('INSERT IGNORE INTO s_articles_similar (articleID, relatedarticle) VALUES (1234, 2)');
        $row = [
            'sku' => 'SW10005',
            'sourceId' => '22',
            'ean' => null,
            'title' => 'Glas -Teetasse 0,25l',
            'shortDescription' => 'Almus Emitto Bos sicut hae Amplitudo rixa ortus retribuo Vicarius an nam capitagium medius.',
            'vendor' =>  [
                'name' => 'Teapavilion',
                'description' => 'Teapavilion description',
                'logo_url' => 'tea_pavilion.jpg',
                'url' => 'http://teapavilion.com',
                'page_title' => 'Teapavilion title',
            ],
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
        ];

        $expectedProduct = new Product($row);
        $expectedProduct->vendor['logo_url'] = 'http:/media/image/8e/42/46/tea_pavilion.jpg';
        $expectedProduct->url = $this->getProductBaseUrl() . '22';
        $expectedProduct->attributes = [
            'quantity' => null,
            'ref_quantity' => null,
        ];

        $expectedProduct->related = [1];
        $expectedProduct->similar = [2];

        $row['vendorName'] = $row['vendor']['name'];
        $row['vendorLink'] = $row['vendor']['url'];
        $row['vendorImage'] = $row['vendor']['logo_url'];
        $row['vendorDescription'] = $row['vendor']['description'];
        $row['vendorMetaTitle'] = $row['vendor']['page_title'];
        $row['detailKind'] = '1';
        unset($row['vendor']);
        $row['category'] = '';
        $row['weight'] = null;
        $row['unit'] = null;
        $row['localId'] = '1234';
        $row['detailId'] = '1234';
        $row['configuratorSetId'] = null;

        $this->assertEquals($expectedProduct, $this->localProductQuery->getConnectProduct($row));
    }

    private function getProductBaseUrl()
    {
        if (!Shopware()->Front()->Router()) {
            return null;
        }

        return Shopware()->Front()->Router()->assemble([
            'module' => 'frontend',
            'controller' => 'connect_product_gateway',
            'action' => 'product',
            'id' => '',
            'fullPath' => true
        ]);
    }

    public function testGetUrlForProduct()
    {
        $expectedUrl = $this->getProductBaseUrl() . '1091';
        $this->assertEquals($expectedUrl, $this->localProductQuery->getUrlForProduct(1091));
    }

    public function testGetUrlForProductWithShopId()
    {
        $expectedUrl = $this->getProductBaseUrl() . '1091/shId/3';
        $this->assertEquals($expectedUrl, $this->localProductQuery->getUrlForProduct(1091, 3));
    }
}
