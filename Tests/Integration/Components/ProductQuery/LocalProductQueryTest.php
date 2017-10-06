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
            ]);
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
