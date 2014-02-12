<?php

namespace Tests\Shopware\Bepado;

use Bepado\SDK\Struct\Product;

class MassImportTest extends BepadoTestHelper
{
    public function testHandleProductUpdates()
    {
        // pseudo verify SDK
        $conn = Shopware()->Db();
        $conn->delete('bepado_shop_config', array());
        $conn->insert('bepado_shop_config', array('s_shop' => '_self_', 's_config' => -1));
        $conn->insert('bepado_shop_config', array('s_shop' => '_last_update_', 's_config' => time()));
        $conn->insert('bepado_shop_config', array('s_shop' => '_categories_', 's_config' => serialize(array('/bücher' => 'Bücher'))));
    }

    protected function getProduct()
    {
        $number = rand(1, 999999999);
        $product =  new \Bepado\SDK\Struct\Product(array(
            'shopId' => 3,
            'revisionId' => time(),
            'sourceId' => $number,
            'ean' => $number,
            'url' => 'http://shopware.de',
            'title' => 'MassImport #'. $number,
            'shortDescription' => 'Ein Produkt aus Bepado',
            'longDescription' => 'Ein Produkt aus Bepado',
            'vendor' => 'Bepado',
            'price' => 9.99,
            'purchasePrice' => 6.99,
            'availability' => 100,
            'categories' => array('/bücher'),
        ));

        return $product;
    }


    public function _testMassImportProducts()
    {
        $commands = array();
        for ($i=0; $i<=60; $i++) {
            $product = $this->getProduct();
            $commands[$product->sourceId] = new \Bepado\SDK\Struct\Change\ToShop\InsertOrUpdate(array(
                'product' => $product,
                'revision' => time(),
            ));
        }

        $start = microtime(true);
        $this->dispatchRpcCall('products', 'toShop', array(
            $commands
        ));

        $end = microtime(true);

        $t = $end - $start;

        echo $t;

    }

}
