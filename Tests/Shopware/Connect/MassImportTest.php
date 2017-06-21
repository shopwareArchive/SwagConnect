<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\ShopwarePlugins\Connect;

use Shopware\Connect\Struct\Product;

class MassImportTest extends ConnectTestHelper
{
    public function testHandleProductUpdates()
    {
        // pseudo verify SDK
        $conn = Shopware()->Db();
        $conn->delete('sw_connect_shop_config', []);
        $conn->insert('sw_connect_shop_config', ['s_shop' => '_self_', 's_config' => -1]);
        $conn->insert('sw_connect_shop_config', ['s_shop' => '_last_update_', 's_config' => time()]);
        $conn->insert('sw_connect_shop_config', ['s_shop' => '_categories_', 's_config' => serialize(['/bücher' => 'Bücher'])]);
    }

    public function _testMassImportProducts()
    {
        $commands = [];
        for ($i = 0; $i <= 60; ++$i) {
            $product = $this->getProduct();
            $commands[$product->sourceId] = new \Shopware\Connect\Struct\Change\ToShop\InsertOrUpdate([
                'product' => $product,
                'revision' => time(),
            ]);
        }

        $start = microtime(true);
        $this->dispatchRpcCall('products', 'toShop', [
            $commands,
        ]);

        $end = microtime(true);

        $t = $end - $start;

        echo $t;
    }
}
