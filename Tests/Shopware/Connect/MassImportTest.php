<?php

namespace Tests\ShopwarePlugins\Connect;

use Shopware\Connect\Struct\Product;

class MassImportTest extends ConnectTestHelper
{
    public function testHandleProductUpdates()
    {
        // pseudo verify SDK
        $conn = Shopware()->Db();
        $conn->delete('sw_connect_shop_config', array());
        $conn->insert('sw_connect_shop_config', array('s_shop' => '_self_', 's_config' => -1));
        $conn->insert('sw_connect_shop_config', array('s_shop' => '_last_update_', 's_config' => time()));
        $conn->insert('sw_connect_shop_config', array('s_shop' => '_categories_', 's_config' => serialize(array('/bücher' => 'Bücher'))));
    }


    public function _testMassImportProducts()
    {
        $commands = array();
        for ($i=0; $i<=60; $i++) {
            $product = $this->getProduct();
            $commands[$product->sourceId] = new \Shopware\Connect\Struct\Change\ToShop\InsertOrUpdate(array(
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
