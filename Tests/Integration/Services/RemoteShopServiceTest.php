<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
use ShopwarePlugins\Connect\Tests\ConnectTestHelperTrait;
use ShopwarePlugins\Connect\Services\RemoteShopService as RemoteShopService;

class RemoteShopServiceTest extends PHPUnit_Framework_TestCase
{
    use ConnectTestHelperTrait;

    /**
     * @var RemoteShopService
     */
    private $remoteShopService;

    public function setUp()
    {
        $this->remoteShopService = new RemoteShopService(
            $this->getSDK()
        );
    }

    public function test_instance()
    {
        $this->assertInstanceOf(RemoteShopService::class, $this->remoteShopService);
    }

    public function test_isPingRemoteShopSuccessful_returns_false()
    {
        $result = $this->remoteShopService->isPingRemoteShopSuccessful(23);
        $this->assertEquals($result, false);
    }

    public function test_isExceptionFatal_returns_false()
    {
        $notFatalExceptionFromSDK = new RuntimeException(
            "Uncaught Shopware\Connect\SecurityException: No Authorization to call service 'ping'."
        );

        $result = $this->remoteShopService->isExceptionFatal($notFatalExceptionFromSDK);
        $this->assertEquals($result, false);
    }

    public function test_isExceptionFatal_returns_true()
    {
        $fatalExceptionFromSDK = new RuntimeException(
            'Some fatal exception from SDK'
        );

        $result = $this->remoteShopService->isExceptionFatal($fatalExceptionFromSDK);
        $this->assertEquals($result, true);
    }
}
