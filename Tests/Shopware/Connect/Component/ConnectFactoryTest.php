<?php

namespace Tests\ShopwarePlugins\Connect\Component;

class ConnectFactoryTest extends \Tests\ShopwarePlugins\Connect\ConnectTestHelper
{

    private $connectFactory;
    private $configMock;
    private $localProductQuery;

    public function setUp()
    {
        parent::setUp();

        $this->connectFactory = $this->getMockBuilder('ShopwarePlugins\Connect\Components\ConnectFactory')
            ->setMethods(array('getConfigComponent', 'getLocalProductQuery', 'getRemoteProductQuery'))
            ->getMock();

        $this->configMock = $this->getMockBuilder('\ShopwarePlugins\Connect\Components\Config')
            ->setConstructorArgs([Shopware()->Models()])
            ->setMethods(['getConfig'])
            ->getMock();

        $this->localProductQuery = $this->getMockBuilder('ShopwarePlugins\Connect\Components\ProductQuery\LocalProductQuery')
            ->disableOriginalConstructor()
            ->getMock();

        $this->connectFactory->method('getConfigComponent')->willReturn($this->configMock);
        $this->connectFactory->method('getLocalProductQuery')->willReturn($this->localProductQuery);

    }

    public function testCreateSDKDefaultLocal()
    {
        $this->configMock->method('getConfig')->willReturn('sn.connect.local');

        $this->assertEquals(null, getenv('_TRANSACTION_HOST'));
        $this->connectFactory->createSDK();

        $this->assertEquals('transaction.connect.local', getenv('_TRANSACTION_HOST'));
    }

    public function testCreateSDKSemLocal()
    {
        putenv("_TRANSACTION_HOST");

        $this->configMock->method('getConfig')->willReturn('semdemo.connect.local');

        $this->assertEquals(null, getenv('_TRANSACTION_HOST'));
        $this->connectFactory->createSDK();

        $this->assertEquals('transaction.connect.local', getenv('_TRANSACTION_HOST'));
    }

    public function testCreateSDKRandomLocal()
    {
        putenv("_TRANSACTION_HOST");
        $prefix = $this->generateRandomString();
        $this->configMock->method('getConfig')->willReturn($prefix . '.connect.local');

        $this->assertEquals(null, getenv('_TRANSACTION_HOST'));
        $this->connectFactory->createSDK();

        $this->assertEquals('transaction.connect.local', getenv('_TRANSACTION_HOST'));
    }

    public function testCreateSDKDefaultStaging()
    {
        putenv("_TRANSACTION_HOST");
        $this->configMock->method('getConfig')->willReturn('sn.stage.connect.shopware.com');

        $this->assertEquals(null, getenv('_TRANSACTION_HOST'));
        $this->connectFactory->createSDK();

        $this->assertEquals('transaction.stage.connect.shopware.com', getenv('_TRANSACTION_HOST'));
    }

    public function testCreateSDKSemStaging()
    {
        putenv("_TRANSACTION_HOST");
        $this->configMock->method('getConfig')->willReturn('sn.sem.stage.connect.shopware.com');

        $this->assertEquals(null, getenv('_TRANSACTION_HOST'));
        $this->connectFactory->createSDK();

        $this->assertEquals('transaction.stage.connect.shopware.com', getenv('_TRANSACTION_HOST'));
    }

    public function testCreateSDKMarketplaceStaging()
    {
        putenv("_TRANSACTION_HOST");
        $prefix = $this->generateRandomString();
        $this->configMock->method('getConfig')->willReturn($prefix . '.stage.connect.shopware.com');

        $this->assertEquals(null, getenv('_TRANSACTION_HOST'));
        $this->connectFactory->createSDK();

        $this->assertEquals('transaction.stage.connect.shopware.com', getenv('_TRANSACTION_HOST'));
    }

    public function testCreateSDKMarketplaceStagingWithMultipleStagings()
    {
        putenv("_TRANSACTION_HOST");
        $prefix = $this->generateRandomString();
        $suffix = '.stage'.rand(1, 9).'.connect.shopware.com';
        $this->configMock->method('getConfig')->willReturn($prefix . $suffix);
        $this->assertEquals(null, getenv('_TRANSACTION_HOST'));
        $this->connectFactory->createSDK();

        $this->assertEquals('transaction' . $suffix, getenv('_TRANSACTION_HOST'));
    }

    public function testCreateSDKLive()
    {
        putenv("_TRANSACTION_HOST");
        //everything that is "unknown" defaults to live (the default value of transactionHost in the DependencyResolver)
        $prefix = $this->generateRandomString(20);
        $this->configMock->method('getConfig')->willReturn($prefix);

        $this->assertEquals(null, getenv('_TRANSACTION_HOST'));
        $this->connectFactory->createSDK();

        $this->assertFalse(getenv('_TRANSACTION_HOST'));
    }

    private function generateRandomString($length = 10)
    {
        putenv("_TRANSACTION_HOST");
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ.';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }

        return $randomString;
    }
}
 