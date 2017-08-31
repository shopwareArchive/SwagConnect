<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Tests\Unit\Subscribers;

use ShopwarePlugins\Connect\Components\Config;
use ShopwarePlugins\Connect\Subscribers\ProductStreams;
use ShopwarePlugins\Connect\Tests\AbstractConnectUnitTest;
use ShopwarePlugins\Connect\Components\Helper;
use Enlight_Components_Db_Adapter_Pdo_Mysql;
use Enlight\Event\SubscriberInterface;
use ShopwarePlugins\Connect\Components\ConnectExport;
use ShopwarePlugins\Connect\Components\ConnectFactory;

class ProductStreamsTest extends AbstractConnectUnitTest
{
    private $subscriber;

    /**
     * @before
     */
    public function prepareMocks()
    {
        $this->subscriber = new ProductStreams(
            $this->createMock(ConnectExport::class),
            $this->createMock(Config::class),
            $this->createMock(Helper::class),
            (new ConnectFactory())->getSDK(),
            $this->createMock(Enlight_Components_Db_Adapter_Pdo_Mysql::class)
        );
    }

    public function test_it_can_be_created()
    {
        $this->assertInstanceOf(SubscriberInterface::class, $this->subscriber);
    }
}
