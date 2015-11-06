<?php

namespace Tests\ShopwarePlugins\Connect;

use Shopware\Connect\Struct\Product;

class SDKTest extends ConnectTestHelper
{
    public function testHandleProductUpdates()
    {
        // pseudo verify SDK
        $conn = Shopware()->Db();
        $conn->delete('bepado_shop_config', array());
        $conn->insert('bepado_shop_config', array('s_shop' => '_self_', 's_config' => -1));
        $conn->insert('bepado_shop_config', array('s_shop' => '_last_update_', 's_config' => time()));
        $conn->insert('bepado_shop_config', array('s_shop' => '_categories_', 's_config' => serialize(array('/bücher' => 'Bücher'))));

        $offerValidUntil = time() + 1 * 365 * 24 * 60 * 60; // One year
        $purchasePrice = 6.99;
        $this->dispatchRpcCall('products', 'toShop', array(
            array(
                new \Shopware\Connect\Struct\Change\ToShop\InsertOrUpdate(array(
                    'product' => new \Shopware\Connect\Struct\Product(array(
                        'shopId' => 3,
                        'revisionId' => time(),
                        'sourceId' => 'ABCDEFGH' . time(),
                        'ean' => '1234',
                        'url' => 'http://shopware.de',
                        'title' => 'shopware Connect Test-Produkt',
                        'shortDescription' => 'Ein Produkt aus shopware Connect',
                        'longDescription' => 'Ein Produkt aus shopware Connect',
                        'vendor' => 'shopware Connect',
                        'price' => 9.99,
                        'purchasePrice' => $purchasePrice,
                        'purchasePriceHash' => hash_hmac(
                            'sha256',
                            sprintf('%.3F %d', $purchasePrice, $offerValidUntil), '54642546-0001-48ee-b4d0-4f54af66d822'
                        ),
                        'offerValidUntil' => $offerValidUntil,
                        'availability' => 100,
                        'images' => array('http://lorempixel.com/400/200'),
                        'categories' => array('/bücher' => 'Bücher'),
                    )),
                    'revision' => time(),
                ))
            )
        ));
    }

    public function testExportProductWithoutPurchasePrice()
    {
        $article = $this->getLocalArticle();
        $prices = $article->getMainDetail()->getPrices();
        $prices[0]->setBasePrice(null);
        Shopware()->Models()->persist($prices[0]);
        Shopware()->Models()->flush();

        $this->getConnectExport()->export(array($article->getId()));


        /** @var \Shopware\CustomModels\Connect\Attribute $model */
        $model = Shopware()->Models()->getRepository('Shopware\CustomModels\Connect\Attribute')->findOneBy(array('sourceId' => $article->getId()));
        $message = $model->getExportMessage();

        $this->assertContains('purchasePrice', $message);

    }

    public function testExportProductWithPurchasePrice()
    {
        // Assign a category mapping
//        $this->changeCategoryConnectMappingForCategoryTo(14, '/bücher');

        $article = $this->getLocalArticle();
        // Insert the product
        $this->getConnectExport()->export(array($article->getId()));

        /** @var \Shopware\CustomModels\Connect\Attribute $model */
        $model = Shopware()->Models()->getRepository('Shopware\CustomModels\Connect\Attribute')->findOneBy(array('articleId' => 3));
        $message = $model->getExportMessage();

        $this->assertNull($message);
    }

}
