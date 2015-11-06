<?php

namespace Tests\ShopwarePlugins\Connect;


use Behat\SahiClient\Exception\AbstractException;
use Shopware\Connect\Struct\Address;
use Shopware\Connect\Struct\Order;
use Shopware\Connect\Struct\OrderItem;
use Shopware\Connect\Struct\PaymentStatus;
use Shopware\Connect\Struct\Product;
use Shopware\Connect\Struct\ShippingCosts;
use ShopwarePlugins\Connect\Components\Logger;
use ShopwarePlugins\Connect\Components\ProductFromShop;

class ProductFromShopTest extends ConnectTestHelper
{
    public function testBuy()
    {
        $fromShop = new ProductFromShop(
            $this->getHelper(),
            Shopware()->Models(),
            new \Shopware\Connect\Gateway\PDO(Shopware()->Db()->getConnection()),
            new Logger(Shopware()->Db())
        );

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
        $fromShop = new ProductFromShop(
            $this->getHelper(),
            Shopware()->Models(),
            new \Shopware\Connect\Gateway\PDO(Shopware()->Db()->getConnection()),
            new Logger(Shopware()->Db())
        );

        $address = new Address(array());
        $fromShop->buy(new Order(array(
            'billingAddress' => $address,
            'deliveryAddress' => $address,
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
//        Shopware()->Plugins()->Backend()->Auth()->setNoAuth();
//        $fromShop = new ProductFromShop($this->getHelper(), Shopware()->Models(), new Logger(Shopware()->Db()));
//
//        $address = new Address(array(
//            'firstName' => 'John',
//            'surName' => 'Doe',
//            'zip' => '48153',
//            'street' => 'Eggeroderstraße',
//            'streetNumber' => '6',
//            'city' => 'Schöppingen',
//            'country' => 'DEU',
//            'email' => 'info@shopware.com',
//            'phone' => '0000123'
//        ));
//
//        $localOrderId = rand(0, 99999);
//        $orderNumber = $fromShop->buy(new Order(array(
//            'orderShop' => '3',
//            'localOrderId' => $localOrderId,
//            'deliveryAddress' => $address,
//            'billingAddress' => $address,
//            'products' => array(
//                new OrderItem(array(
//                    'count' => 1,
//                    'product' => new Product(array(
//                        'shopId' => '3',
//                        'sourceId' => '2',
//                        'price' => 44.44,
//                        'purchasePrice' => 33.33,
//                        'fixedPrice' => false,
//                        'currency' => 'EUR',
//                        'availability' => 3,
//                        'title' => 'Milchschnitte',
//                        'categories' => array()
//                    ))
//                ))
//            )
//        )));
//
//        $paymentStatus = new PaymentStatus(array(
//            'localOrderId' => $orderNumber,
//            'paymentStatus' => 'received',
//            'paymentProvider' => 'paypal',
//            'providerTransactionId' => 'pp1234567890',
//            'revision' => '1431090080.0525200000',
//        ));
// todo: disable OrderHistorySubscriber
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
//            $commands[] = new \Shopware\Connect\Struct\Change\ToShop\InsertOrUpdate(array(
//                'product' => $product,
//                'revision' => time(),
//            ));
//        }

//        $this->dispatchRpcCall('productPayments', 'replicate', array(
//            $commands
//        ));

//        return array_keys($commands);

    }

    public function testCalculateShippingCosts()
    {
        $mockGateway = $this->getMockBuilder('Shopware\Connect\Gateway\PDO')
            ->disableOriginalConstructor()
            ->getMock();
        $mockGateway->expects($this->any())->method('getShopId')->willReturn(25);

        $fromShop = new ProductFromShop(
            $this->getHelper(),
            Shopware()->Models(),
            $mockGateway,
            new Logger(Shopware()->Db())
        );

        $order = $this->createOrder();

        $request = new \Enlight_Controller_Request_RequestTestCase();
        Shopware()->Front()->setRequest($request);

        Shopware()->Session()->offsetSet('sDispatch', 9);

        $result = $fromShop->calculateShippingCosts($order);

        $this->assertInstanceOf('Shopware\Connect\Struct\Shipping', $result);
        $this->assertTrue($result->isShippable);
        $this->assertEquals($result->shippingCosts, 3.28);
        $this->assertEquals($result->grossShippingCosts, 3.9);
        $this->assertEquals(25, $result->shopId);
        $this->assertEquals('Standard Versand', $result->service);
    }

    public function testCalculateShippingCostsWithoutCountry()
    {
        $fromShop = new ProductFromShop(
            $this->getHelper(),
            Shopware()->Models(),
            new \Shopware\Connect\Gateway\PDO(Shopware()->Db()->getConnection()),
            new Logger(Shopware()->Db())
        );

        $order = new Order();
        $shippingCosts = $fromShop->calculateShippingCosts($order);
        $this->assertFalse($shippingCosts->isShippable);
    }

    /**
     * @expectedException        \InvalidArgumentException
     * @expectedExceptionMessage ProductList is not allowed to be empty
     */
    public function testCalculateShippingCostsWithoutOrderItems()
    {
        $fromShop = new ProductFromShop(
            $this->getHelper(),
            Shopware()->Models(),
            new \Shopware\Connect\Gateway\PDO(Shopware()->Db()->getConnection()),
            new Logger(Shopware()->Db())
        );

        $order = $this->createOrder();
        $order->orderItems = array();

        $request = new \Enlight_Controller_Request_RequestTestCase();
        Shopware()->Front()->setRequest($request);

        Shopware()->Session()->offsetSet('sDispatch', 9);

        $shippingCosts = $fromShop->calculateShippingCosts($order);

        $this->assertFalse($shippingCosts->isShippable);
    }

    private function createOrder()
    {
        $localArticle = $this->getLocalArticle();
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

        $repository = Shopware()->Models()->getRepository('Shopware\CustomModels\Connect\Attribute');
        $attribute = $repository->findOneBy(array('articleDetailId' => $localArticle->getMainDetail()->getId(), 'shopId' => null));

        return new Order(array(
            'orderShop' => '3',
            'localOrderId' => $localOrderId,
            'deliveryAddress' => $address,
            'billingAddress' => $address,
            'products' => array(
                new OrderItem(array(
                    'count' => 1,
                    'product' => new Product(array(
                        'shopId' => '3',
                        'sourceId' => $attribute->getSourceId(),
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
        ));
    }
}