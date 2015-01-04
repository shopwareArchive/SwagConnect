<?php

namespace Tests\Shopware\Bepado;


use Bepado\SDK\Struct\Address;
use Bepado\SDK\Struct\Order;
use Bepado\SDK\Struct\OrderItem;
use Bepado\SDK\Struct\Product;
use Shopware\Bepado\Components\ProductFromShop;

class ProductFromShopTest extends BepadoTestHelper
{
    public function testBuy()
    {
        $fromShop = new ProductFromShop($this->getHelper(), Shopware()->Models());

        $address = new Address(array(
            'firstName' => 'John',
            'surName' => 'Doe',
            'zip' => '48153',
            'street' => 'Eggeroderstraße',
            'streetNumber' => '6',
            'city' => 'Schöppingen',
            'country' => 'DEU',
            'email' => 'info@shopware.com',
            'phone' => '0000123'
        ));
        $orderNumber = $fromShop->buy(new Order(array(
            'orderShop' => '3',
            'localOrderId' => rand(0, 99999),
            'deliveryAddress' => $address,
            'billingAddress' => $address,
            'products' => array(
                new OrderItem(array(
                    'count' => 1,
                    'product' => new Product(array(
                        'shopId' => '3',
                        'sourceId' => '2',
                        'price' => 44.44,
                        'purchasePrice' => 33.33,
                        'fixedPrice' => false,
                        'currency' => 'EUR',
                        'availability' => 3,
                        'title' => 'Milchschnitte',
                        'categories' => array()
                    ))
                ))
            )
        )));

        $order = $this->getOrderByNumber($orderNumber);
        $this->assertEquals($orderNumber, $order->getNumber());
    }

    /**
     * @expectedException \Doctrine\DBAL\DBALException
     */
    public function testByShouldThrowException()
    {
        $fromShop = new ProductFromShop($this->getHelper(), Shopware()->Models());

        $fromShop->buy(new Order(array(
        )));
    }

    /**
     * @param $orderNumber
     * @return \Shopware\Models\Order\Order
     */
    public function getOrderByNumber($orderNumber)
    {
        return Shopware()->Models()->getRepository('Shopware\Models\Order\Order')->findOneBy(array('number' => $orderNumber));
    }
}