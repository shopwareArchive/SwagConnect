<?php

namespace Tests\Shopware\Bepado;

use Bepado\SDK\Struct\Product;

class SDKTest extends BepadoTestHelper
{
    public function testHandleProductUpdates()
    {
        // pseudo verify SDK
        $conn = Shopware()->Db();
        $conn->delete('bepado_shop_config', array());
        $conn->insert('bepado_shop_config', array('s_shop' => '_self_', 's_config' => -1));
        $conn->insert('bepado_shop_config', array('s_shop' => '_last_update_', 's_config' => time()));
        $conn->insert('bepado_shop_config', array('s_shop' => '_categories_', 's_config' => serialize(array('/bücher' => 'Bücher'))));

        $this->dispatchRpcCall('products', 'toShop', array(
            array(
                new \Bepado\SDK\Struct\Change\ToShop\InsertOrUpdate(array(
                    'product' => new \Bepado\SDK\Struct\Product(array(
                        'shopId' => 3,
                        'revisionId' => time(),
                        'sourceId' => 'ABCDEFGH' . time(),
                        'ean' => '1234',
                        'url' => 'http://shopware.de',
                        'title' => 'Bepado Test-Produkt',
                        'shortDescription' => 'Ein Produkt aus Bepado',
                        'longDescription' => 'Ein Produkt aus Bepado',
                        'vendor' => 'Bepado',
                        'price' => 9.99,
                        'purchasePrice' => 6.99,
                        'availability' => 100,
                        'images' => array('http://lorempixel.com/400/200'),
                        'categories' => array('/bücher'),
                    )),
                    'revision' => time(),
                ))
            )
        ));
    }

    public function testExportProductWithoutPurchasePrice()
    {
        $this->getBepadoExport()->export(array(14));

        /** @var \Shopware\CustomModels\Bepado\Attribute $model */
        $model = Shopware()->Models()->getRepository('Shopware\CustomModels\Bepado\Attribute')->findOneBy(array('sourceId' => 14));
        $message = $model->getExportMessage();

        $this->assertContains('purchasePrice', $message);

    }

    public function testExportProductWithPurchasePrice()
    {
        // Set a purchase price
        /** @var \Shopware\Models\Article\Price $price */
        $price = Shopware()->Models()->getRepository('Shopware\Models\Article\Price')->findOneBy(array('articleDetailsId' => 3, 'from' => 1, 'customerGroup' => 'EK'));
        $price->setBasePrice($price->getPrice());
        Shopware()->Models()->flush();

        // Assign a category mapping
        $this->changeCategoryBepadoMappingForCategoryTo(14, '/bücher');

        // Insert the product
        $this->getBepadoExport()->export(array(3));

        /** @var \Shopware\CustomModels\Bepado\Attribute $model */
        $model = Shopware()->Models()->getRepository('Shopware\CustomModels\Bepado\Attribute')->findOneBy(array('articleId' => 3));
        $message = $model->getExportMessage();

        $this->assertNull($message);
    }

}
