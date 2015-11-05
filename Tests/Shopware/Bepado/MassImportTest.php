<?php

namespace Tests\Shopware\Connect;

use Bepado\SDK\Struct\Product;

class MassImportTest extends ConnectTestHelper
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
