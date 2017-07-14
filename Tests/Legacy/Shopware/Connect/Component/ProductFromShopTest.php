<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\ShopwarePlugins\Connect\Component;

use Shopware\Connect\Struct\Address;
use Shopware\Connect\Struct\Change\FromShop\Availability;
use Shopware\Connect\Struct\Change\FromShop\Update;
use Shopware\Connect\Struct\Change\FromShop\Delete;
use Shopware\Connect\Struct\Order;
use Shopware\Connect\Struct\OrderItem;
use Shopware\Connect\Struct\Product;
use Shopware\CustomModels\Connect\Attribute;
use Shopware\Models\Article\Article;
use ShopwarePlugins\Connect\Components\Logger;
use ShopwarePlugins\Connect\Components\ProductFromShop;
use Shopware\Connect\Struct\Change\FromShop\Insert;
use Tests\ShopwarePlugins\Connect\ConnectTestHelper;

class ProductFromShopTest extends ConnectTestHelper
{
    /**
     * @var array
     */
    private $user;

    /**
     * @var ProductFromShop
     */
    private $productFromShop;

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
        $translationData = [
            'dispatch_name' => 'Standard delivery',
            'dispatch_status_link' => 'http://track.me',
            'dispatch_description' => 'Standard delivery description',
        ];
        $translator->write(2, 'config_dispatch', 9, $translationData, true);
    }

    public function setUp()
    {
        parent::setUp();

        $this->user = $this->getRandomUser();
        $this->user['billingaddress']['country'] = $this->user['billingaddress']['countryID'];
        Shopware()->Events()->addListener('Shopware_Modules_Admin_GetUserData_FilterResult', [$this, 'onGetUserData']);


        $this->productFromShop = new ProductFromShop(
            $this->getHelper(),
            Shopware()->Models(),
            new \Shopware\Connect\Gateway\PDO(Shopware()->Db()->getConnection()),
            new Logger(Shopware()->Db()),
            Shopware()->Container()->get('events')
        );
    }

    public function onGetUserData(\Enlight_Event_EventArgs $args)
    {
        $args->setReturn($this->user);
    }

    public function testBuy()
    {
        $address = new Address([
            'firstName' => 'John',
            'surName' => 'Doe',
            'zip' => '48153',
            'street' => 'Eggeroderstraße',
            'streetNumber' => '6',
            'city' => 'Schöppingen',
            'country' => 'DEU',
            'email' => 'info@shopware.com',
            'phone' => '0000123'
        ]);
        $orderNumber = $this->productFromShop->buy(new Order([
            'orderShop' => '3',
            'localOrderId' => rand(0, 99999),
            'deliveryAddress' => $address,
            'billingAddress' => $address,
            'products' => [
                new OrderItem([
                    'count' => 1,
                    'product' => new Product([
                        'shopId' => '3',
                        'sourceId' => '2',
                        'price' => 44.44,
                        'purchasePrice' => 33.33,
                        'fixedPrice' => false,
                        'currency' => 'EUR',
                        'availability' => 3,
                        'title' => 'Milchschnitte',
                        'categories' => []
                    ])
                ])
            ]
        ]));

        $order = $this->getOrderByNumber($orderNumber);
        $this->assertEquals($orderNumber, $order->getNumber());
    }

    /**
     * @param $orderNumber
     * @return \Shopware\Models\Order\Order
     */
    public function getOrderByNumber($orderNumber)
    {
        return Shopware()->Models()->getRepository('Shopware\Models\Order\Order')->findOneBy(['number' => $orderNumber]);
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
        $order = new Order();
        $shippingCosts = $this->productFromShop->calculateShippingCosts($order);
        $this->assertFalse($shippingCosts->isShippable);
    }

    /**
     * @expectedException        \InvalidArgumentException
     * @expectedExceptionMessage ProductList is not allowed to be empty
     */
    public function testCalculateShippingCostsWithoutOrderItems()
    {
        $order = $this->createOrder();
        $order->orderItems = [];

        $request = new \Enlight_Controller_Request_RequestTestCase();
        Shopware()->Front()->setRequest($request);

        Shopware()->Session()->offsetSet('sDispatch', 9);

        $shippingCosts = $this->productFromShop->calculateShippingCosts($order);

        $this->assertFalse($shippingCosts->isShippable);
    }

    private function createOrder(Article $localArticle = null)
    {
        if (!$localArticle) {
            $localArticle = $this->getLocalArticle();
        }

        $address = new Address([
            'firstName' => 'John',
            'surName' => 'Doe',
            'zip' => '48153',
            'street' => 'Eggeroderstraße',
            'streetNumber' => '6',
            'city' => 'Schöppingen',
            'country' => 'DEU',
            'email' => 'info@shopware.com',
            'phone' => '0000123'
        ]);

        $localOrderId = rand(0, 99999);

        $repository = Shopware()->Models()->getRepository('Shopware\CustomModels\Connect\Attribute');
        $attribute = $repository->findOneBy(['articleDetailId' => $localArticle->getMainDetail()->getId(), 'shopId' => null]);

        return new Order([
            'orderShop' => '3',
            'localOrderId' => $localOrderId,
            'deliveryAddress' => $address,
            'billingAddress' => $address,
            'products' => [
                new OrderItem([
                    'count' => 1,
                    'product' => new Product([
                        'shopId' => '3',
                        'sourceId' => $attribute->getSourceId(),
                        'price' => 44.44,
                        'purchasePrice' => 33.33,
                        'fixedPrice' => false,
                        'currency' => 'EUR',
                        'availability' => 3,
                        'title' => 'Milchschnitte',
                        'categories' => []
                    ])
                ])
            ]
        ]);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testByShouldThrowException()
    {
        $address = new Address([]);
        $this->productFromShop->buy(new Order([
            'billingAddress' => $address,
            'deliveryAddress' => $address,
        ]));
    }

    public function testOnPerformSync()
    {
        // reset export_status for all local products
        Shopware()->Db()->executeQuery(
            'UPDATE s_plugin_connect_items SET export_status = NULL WHERE shop_id IS NULL'
        );

        $time = microtime(true);
        $iteration = 0;
        $changes = [];

        // create local product and set revision lower than $since
        // that means the product is already exported to Connect
        // and his status is "insert".
        // When onPerformSync method is called, this product should be with status "synced"
        $syncedProduct = $this->getLocalArticle();
        Shopware()->Db()->executeQuery(
            'UPDATE s_plugin_connect_items SET revision = ?, export_status = ? WHERE source_id = ? AND shop_id IS NULL',
            [
                sprintf('%.5f%05d', $time, $iteration++),
                Attribute::STATUS_INSERT,
                $syncedProduct->getId()
            ]
        );

        // create local product and set revision lower than $since
        // that means the product is already synced with connect.
        // current status is "delete".
        // When onPerformSync method is called, this product should be with status "NULL"
        $deletedProduct = $this->getLocalArticle();
        Shopware()->Db()->executeQuery(
            'UPDATE s_plugin_connect_items SET revision = ?, export_status = ? WHERE source_id = ? AND shop_id IS NULL',
            [
                sprintf('%.5f%05d', $time, $iteration++),
                Attribute::STATUS_DELETE,
                $deletedProduct->getId()
            ]
        );

        $since = sprintf('%.5f%05d', $time, $iteration++);

        // generate 5 changes
        // their status is "insert" or "update"
        // and it won't be changed, because revision is greater than $since
        for ($i = 0; $i < 5; ++$i) {
            $product = $this->getLocalArticle();
            Shopware()->Db()->executeQuery(
                'UPDATE s_plugin_connect_items SET export_status = ? WHERE source_id = ? AND shop_id IS NULL',
                [
                    Attribute::STATUS_INSERT,
                    $product->getId()
                ]
            );
            $changes[] = new Insert([
                'product' => $product,
                'sourceId' => $product->getId(),
                'revision' => sprintf('%.5f%05d', $time, $iteration++)
            ]);
        }

        $product = $this->getLocalArticle();
        Shopware()->Db()->executeQuery(
            'UPDATE s_plugin_connect_items SET export_status = ? WHERE source_id = ? AND shop_id IS NULL',
            [
                Attribute::STATUS_UPDATE,
                $product->getId()
            ]
        );
        $changes[] = new Update([
            'product' => $product,
            'sourceId' => $product->getId(),
            'revision' => sprintf('%.5f%05d', $time, $iteration++)
        ]);

        $product = $this->getLocalArticle();
        Shopware()->Db()->executeQuery(
            'UPDATE s_plugin_connect_items SET export_status = ? WHERE source_id = ? AND shop_id IS NULL',
            [
                Attribute::STATUS_UPDATE,
                $product->getId()
            ]
        );
        $changes[] = new Availability([
            'availability' => 5,
            'sourceId' => $product->getId(),
            'revision' => sprintf('%.5f%05d', $time, $iteration++)
        ]);

        $this->productFromShop->onPerformSync($since, $changes);

        $result = Shopware()->Db()->fetchAll(
            'SELECT source_id
                FROM s_plugin_connect_items
                WHERE shop_id IS NULL AND export_status = "synced"'
        );

        // verify that only 1 product has status "synced"
        $this->assertEquals(1, count($result));
        // verify that this product is exactly the same
        $this->assertEquals($syncedProduct->getId(), $result[0]['source_id']);

        // verify that deleted product has export_status NULL
        $result = Shopware()->Db()->fetchCol(
            'SELECT export_status
                FROM s_plugin_connect_items
                WHERE source_id = ? AND shop_id IS NULL',
            [$deletedProduct->getId()]
        );
        $this->assertEmpty(reset($result));

        // verify that each of these 5 changes have
        // correct revision in s_plugin_connect_items table
        // after onPerformSync
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

    public function testBuyShouldFireFilterEventWithOrder()
    {
        $order = $this->createOrder();

        /** @var \Enlight_Event_EventManager|\PHPUnit_Framework_MockObject_MockObject $eventManagerMock */
        $eventManagerMock = $this->createMock(\Enlight_Event_EventManager::class);
        $eventManagerMock->method('filter')
            ->with('Connect_Components_ProductFromShop_Buy_OrderFilter', $order)
            ->willReturn($order);

        $fromShop = new ProductFromShop(
            $this->getHelper(),
            Shopware()->Models(),
            new \Shopware\Connect\Gateway\PDO(Shopware()->Db()->getConnection()),
            new Logger(Shopware()->Db()),
            $eventManagerMock
        );

        $result = $fromShop->buy($order);

        $this->assertStringStartsWith('SC-', $result);
    }
}
