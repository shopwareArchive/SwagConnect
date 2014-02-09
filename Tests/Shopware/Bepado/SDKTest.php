<?php

namespace Shopware\Bepado\Components;

use Enlight_Components_Test_Plugin_TestCase;
use Bepado\SDK\Struct\Product;
use Shopware\Bepado\Components\BepadoFactory;

class SDKTest extends Enlight_Components_Test_Plugin_TestCase
{
    public function testHandleProductUpdates()
    {
        $sdk = Shopware()->Bootstrap()->getResource('BepadoSDK');

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
                        'sourceId' => 'ABCDEFG',
                        'ean' => '1234',
                        'url' => 'http://shopware.de',
                        'title' => 'Bepado Test-Produkt',
                        'shortDescription' => 'Ein Produkt aus Bepado',
                        'longDescription' => 'Ein Produkt aus Bepado',
                        'vendor' => 'Bepado',
                        'price' => 9.99,
                        'purchasePrice' => 6.99,
                        'availability' => 100,
                        'categories' => array('/bücher'),
                    )),
                    'revision' => time(),
                ))
            )
        ));
    }

    private function dispatchRpcCall($service, $command, array $args)
    {
        $sdk = Shopware()->Bootstrap()->getResource('BepadoSDK');
        $refl = new \ReflectionObject($sdk);
        $property = $refl->getProperty('dependencies');
        $property->setAccessible(true);
        $deps = $property->getValue($sdk);
        $serviceRegistry = $deps->getServiceRegistry();
        $callable = $serviceRegistry->getService($service, $command);

        return call_user_func_array(array($callable['provider'], $callable['command']), $args);
    }
}
