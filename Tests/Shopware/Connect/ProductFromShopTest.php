<?php

namespace Tests\ShopwarePlugins\Connect;

use Shopware\Connect\Struct\Address;
use Shopware\Connect\Struct\Change\FromShop\Availability;
use Shopware\Connect\Struct\Change\FromShop\Update;
use Shopware\Connect\Struct\Order;
use Shopware\Connect\Struct\OrderItem;
use Shopware\Connect\Struct\Product;
use Shopware\Models\Article\Article;
use ShopwarePlugins\Connect\Components\Logger;
use ShopwarePlugins\Connect\Components\ProductFromShop;
use Shopware\Connect\Struct\Change\FromShop\Insert;
use Shopware\Bundle\AttributeBundle\Service\CrudService;

class ProductFromShopTest extends ConnectTestHelper
{
    private $user;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        $manager = Shopware()->Models();
        /** @var \Shopware\Models\Shop\Shop $defaultShop */
        $defaultShop = $manager->getRepository('Shopware\Models\Shop\Shop')->find(1);
        /** @var \Shopware\Models\Shop\Shop $fallbackShop */
        $fallbackShop = $manager->getRepository('Shopware\Models\Shop\Shop')->find(2);
        $defaultShop->setFallback($fallbackShop);
        $manager->persist($defaultShop);
        $manager->flush();

        $translator = new \Shopware_Components_Translation();
        $translationData = array (
            'dispatch_name' => 'Standard delivery',
            'dispatch_status_link' => 'http://track.me',
            'dispatch_description' => 'Standard delivery description',
        );
        $translator->write(2, 'config_dispatch', 9, $translationData, true);
    }

    public function setUp()
    {
        parent::setUp();

        $this->user = $this->getRandomUser();
        $this->user['billingaddress']['country'] = $this->user['billingaddress']['countryID'];
        Shopware()->Events()->addListener('Shopware_Modules_Admin_GetUserData_FilterResult', [$this, 'onGetUserData']);
    }

    public function onGetUserData(\Enlight_Event_EventArgs $args)
    {
        $args->setReturn($this->user);
    }

    public function testBuy()
    {
        $fromShop = new ProductFromShop(
            $this->getHelper(),
            Shopware()->Models(),
            new \Shopware\Connect\Gateway\PDO(Shopware()->Db()->getConnection()),
            new Logger(Shopware()->Db()),
            Shopware()->Container()->get('events')
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
            new Logger(Shopware()->Db()),
            Shopware()->Container()->get('events')
        );

        // hack for static variable $cache in sAdmin::sGetCountry
        // undefined index countryId when it's called for the first time
        @Shopware()->Modules()->Admin()->sGetCountry(2);

        $localArticle = $this->getLocalArticle();
        $order = $this->createOrder($localArticle);
        Shopware()->Db()->executeQuery(
            'INSERT INTO `s_order_basket`(`sessionID`, `userID`, `articlename`, `articleID`, `ordernumber`, `quantity`, `price`, `netprice`, `tax_rate`, `currencyFactor`)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                Shopware()->Session()->get('sessionId'),
                $this->user['user']['id'],
                $localArticle->getName(),
                $localArticle->getId(),
                $localArticle->getMainDetail()->getNumber(),
                1,
                49.99,
                42.008403361345,
                19,
                1,
            ]
        );

        $request = new \Enlight_Controller_Request_RequestTestCase();
        Shopware()->Front()->setRequest($request);

        Shopware()->Session()->offsetSet('sDispatch', 9);
        Shopware()->Session()->offsetSet('sRegister', ['billing' => $this->user['billingaddress']]);

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
            new Logger(Shopware()->Db()),
            Shopware()->Container()->get('events')
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
            new Logger(Shopware()->Db()),
            Shopware()->Container()->get('events')
        );

        $order = $this->createOrder();
        $order->orderItems = array();

        $request = new \Enlight_Controller_Request_RequestTestCase();
        Shopware()->Front()->setRequest($request);

        Shopware()->Session()->offsetSet('sDispatch', 9);

        $shippingCosts = $fromShop->calculateShippingCosts($order);

        $this->assertFalse($shippingCosts->isShippable);
    }

    private function createOrder(Article $localArticle = null)
    {
        if (!$localArticle) {
            $localArticle = $this->getLocalArticle();
        }

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

    /**
     * @expectedException \RuntimeException
     */
    public function testByShouldThrowException()
    {
        $fromShop = new ProductFromShop(
            $this->getHelper(),
            Shopware()->Models(),
            new \Shopware\Connect\Gateway\PDO(Shopware()->Db()->getConnection()),
            new Logger(Shopware()->Db()),
            Shopware()->Container()->get('events')
        );

        $address = new Address(array());
        $fromShop->buy(new Order(array(
            'billingAddress' => $address,
            'deliveryAddress' => $address,
        )));
    }

    public function testOnPerformSync()
    {
        $fromShop = new ProductFromShop(
            $this->getHelper(),
            Shopware()->Models(),
            new \Shopware\Connect\Gateway\PDO(Shopware()->Db()->getConnection()),
            new Logger(Shopware()->Db()),
            Shopware()->Container()->get('events')
        );

        $time = microtime(true);
        $iteration = 0;
        $changes = [];

        $syncedProduct = $this->getLocalArticle();
        Shopware()->Db()->executeQuery(
            'UPDATE s_plugin_connect_items SET revision = ? WHERE source_id = ? AND shop_id IS NULL',
            [
                sprintf('%.5f%05d', $time, $iteration++),
                $syncedProduct->getId()
            ]
        );

        $since = sprintf('%.5f%05d', $time, $iteration++);

        for ($i = 0; $i < 5; $i++) {
            $product = $this->getLocalArticle();
            $changes[] = new Insert([
                'product' => $product,
                'sourceId' => $product->getId(),
                'revision' => sprintf('%.5f%05d', $time, $iteration++)
            ]);
        }

        $product = $this->getLocalArticle();
        $changes[] = new Update([
            'product' => $product,
            'sourceId' => $product->getId(),
            'revision' => sprintf('%.5f%05d', $time, $iteration++)
        ]);

        $product = $this->getLocalArticle();
        $changes[] = new Availability([
            'availability' => 5,
            'sourceId' => $product->getId(),
            'revision' => sprintf('%.5f%05d', $time, $iteration++)
        ]);

        $fromShop->onPerformSync($since, $changes);

        $result = Shopware()->Db()->fetchCol(
            'SELECT COUNT(*)
                FROM s_plugin_connect_items
                WHERE source_id = ? AND shop_id IS NULL AND export_status = "synced"',
            [$syncedProduct->getId()]
        );
        $this->assertEquals(1, reset($result));

        foreach ($changes as $change) {
            $result = Shopware()->Db()->fetchCol(
                'SELECT revision
                FROM s_plugin_connect_items
                WHERE source_id = ? AND shop_id IS NULL',
                [$change->sourceId]
            );

            $this->assertEquals($change->revision, reset($result));
        }
    }
}