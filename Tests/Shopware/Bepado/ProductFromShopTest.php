<?php

namespace Tests\Shopware\Bepado;


use Bepado\SDK\Struct\Address;
use Bepado\SDK\Struct\Order;
use Bepado\SDK\Struct\OrderItem;
use Bepado\SDK\Struct\PaymentStatus;
use Bepado\SDK\Struct\Product;
use Shopware\Bepado\Components\Logger;
use Shopware\Bepado\Components\ProductFromShop;
use Symfony\Component\Debug\Debug;

class ProductFromShopTest extends BepadoTestHelper
{
    public function testBuy()
    {
        $fromShop = new ProductFromShop($this->getHelper(), Shopware()->Models(), new Logger(Shopware()->Db()));

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
        $fromShop = new ProductFromShop($this->getHelper(), Shopware()->Models(), new Logger(Shopware()->Db()));

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

    public function testUpdatePaymentStatus()
    {
        Shopware()->Plugins()->Backend()->Auth()->setNoAuth();
        $fromShop = new ProductFromShop($this->getHelper(), Shopware()->Models(), new Logger(Shopware()->Db()));

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

        $localOrderId = rand(0, 99999);
        $orderNumber = $fromShop->buy(new Order(array(
            'orderShop' => '3',
            'localOrderId' => $localOrderId,
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

        $paymentStatus = new PaymentStatus(array(
            'localOrderId' => $orderNumber,
            'paymentStatus' => 'received',
            'paymentProvider' => 'paypal',
            'providerTransactionId' => 'pp1234567890',
            'revision' => '1431090080.0525200000',
        ));

//        foreach (Shopware()->Models()->getEventManager()->getListeners('preUpdate') as $listener) {
////            if (get_class($listener) == 'Shopware\Models\Order\OrderHistorySubscriber') {
//                Shopware()->Models()->getEventManager()->removeEventSubscriber($listener);
////            }
//        }
//        \Doctrine\Common\Util\Debug::dump(Shopware()->Models()->getEventManager()->hasListeners('preUpdate'));exit;
//        $a = new \Doctrine\Common\EventManager();
//        $a->removeEventListener()
//        $a->removeEventSubscriber('Shopware\Models\Order\OrderHistorySubscriber');
//        try {
//        $order = Shopware()->Models()->getRepository('Shopware\Models\Order\Order')->findOneBy(array('number' => $orderNumber));

//        \Doctrine\Common\Util\Debug::dump(Shopware()->Models()->getClassMetadata('Shopware\Models\Order\Order')->get);exit;
//        Shopware()->Models()->getClassMetadata('Shopware\Models\Order\Order')->getLifecycleCallbacks()
//            setLifecycleCallbacks(array());

//            $fromShop->updatePaymentStatus($paymentStatus);
//        } catch (\Exception $e) {
//            var_dump($e->getMessage());exit;
//        }

//        $commands = array(
//            $paymentStatus
//        );
//            $commands[] = new \Bepado\SDK\Struct\Change\ToShop\InsertOrUpdate(array(
//                'product' => $product,
//                'revision' => time(),
//            ));
//        }

//        $this->dispatchRpcCall('productPayments', 'replicate', array(
//            $commands
//        ));
//var_dump('sb:das');exit;
//        return array_keys($commands);

    }
}