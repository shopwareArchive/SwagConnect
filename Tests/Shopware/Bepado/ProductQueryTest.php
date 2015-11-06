<?php

namespace Tests\ShopwarePlugins\Connect;

use Shopware\Connect\Struct\Verificator\Product;
use ShopwarePlugins\Connect\Components\Config;
use ShopwarePlugins\Connect\Components\Gateway\ProductTranslationsGateway\PdoProductTranslationsGateway;
use ShopwarePlugins\Connect\Components\Marketplace\MarketplaceGateway;
use ShopwarePlugins\Connect\Components\ProductQuery;
use ShopwarePlugins\Connect\Components\ProductQuery\RemoteProductQuery;
use ShopwarePlugins\Connect\Components\ProductQuery\LocalProductQuery;
use ShopwarePlugins\Connect\Components\Translations\ProductTranslator;

class ProductQueryTest extends ConnectTestHelper
{
    protected $productQuery;

    protected $productTranslator;

    public function getProductQuery()
    {
        if (!$this->productQuery) {
            $this->productTranslator = $this->getMockBuilder('\\Shopware\\Connect\\Components\\Translations\\ProductTranslator')
                ->disableOriginalConstructor()
                ->getMock();

            $this->productTranslator->expects($this->any())
                ->method('translate')
                ->willReturn(array());

            /** @var \ShopwarePlugins\Connect\Components\Config $configComponent */
            $configComponent = new Config(Shopware()->Models());

            $this->productQuery = new ProductQuery(
                new LocalProductQuery(
                    Shopware()->Models(),
                    $configComponent->getConfig('alternateDescriptionField'),
                    $this->getProductBaseUrl(),
                    $configComponent,
                    new MarketplaceGateway(Shopware()->Models()),
                    $this->productTranslator
                ),
                new RemoteProductQuery(Shopware()->Models(), $configComponent->getConfig('alternateDescriptionField'))
            );
        }
        return $this->productQuery;
    }

    public function getShopProduct($id)
    {
        return Shopware()->Models()->getRepository('Shopware\Models\Article\Article')->find($id);
    }


    public function testGetLocal()
    {
        $result = $this->getProductQuery()->getLocal(array(3));
        /** @var \Shopware\Connect\Struct\Product $product */
        $product = $result[0];

        $this->assertInstanceOf('\Shopware\Connect\Struct\Product', $product);
        $this->assertEquals('Münsterländer Aperitif 16%', $product->title);
        $this->assertEquals(12.56, round($product->price, 2));
    }

    public function testGetRemoteShouldReturnEmptyArray()
    {
        $result = $this->getProductQuery()->getRemote(array(2));
        $this->assertEmpty($result);
    }

    public function testGetRemote()
    {
        $newProduct = $this->getProduct();
        $this->dispatchRpcCall('products', 'toShop', array(
            array(
                new \Shopware\Connect\Struct\Change\ToShop\InsertOrUpdate(array(
                    'product' => $newProduct,
                    'revision' => time(),
                ))
            )
        ));

        $result = $this->getProductQuery()->getRemote(array($newProduct->sourceId));
        /** @var \Shopware\Connect\Struct\Product $product */
        $product = $result[0];

        $this->assertInstanceOf('\Shopware\Connect\Struct\Product', $product);
        $this->assertEquals($newProduct->title, $product->title);
        $this->assertEquals($newProduct->price, $product->price);
        $this->assertEquals($newProduct->purchasePrice, $product->purchasePrice);
        $this->assertEquals($newProduct->purchasePriceHash, $product->purchasePriceHash);
        $this->assertEquals($newProduct->offerValidUntil, $product->offerValidUntil);

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

    public function testGetConnectProduct()
    {
        $result = $this->getProductQuery()->getLocal(array(3));
        /** @var \Shopware\Connect\Struct\Product $product */
        $product = $result[0];

        $this->assertEquals('Münsterländer Aperitif 16%', $product->title);
        $this->assertEquals('l', $product->attributes['unit']);
        $this->assertEquals('0.7000', $product->attributes['quantity']);
        $this->assertEquals('1.000', $product->attributes['ref_quantity']);


        $model = Shopware()->Models()->getRepository('Shopware\Models\Article\Article')->find(11);
        $connectAttribute = $this->getHelper()->getOrCreateConnectAttributeByModel($model);
        $result = $this->getProductQuery()->getLocal(array(11));
        /** @var \Shopware\Connect\Struct\Product $product */
        $product = $result[0];

        $this->assertEquals('Münsterländer Aperitif Präsent Box', $product->title);
        $this->assertEmpty($product->attributes['unit']);
        $this->assertNull($product->attributes['quantity']);
        $this->assertNull($product->attributes['ref_quantity']);
    }
}
