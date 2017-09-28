<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Tests\Unit\Subscribers;

use ShopwarePlugins\Connect\Subscribers\ControllerPath;
use ShopwarePlugins\Connect\Tests\AbstractConnectUnitTest;
use Enlight\Event\SubscriberInterface;

class ControllerPathTest extends AbstractConnectUnitTest
{
    public function test_it_can_be_created()
    {
        $subscriber = new ControllerPath('some/fake/path');

        $this->assertInstanceOf(SubscriberInterface::class, $subscriber);
        $this->assertInstanceOf(ControllerPath::class, $subscriber);
    }

    public function test_subscribed_events()
    {
        $this->assertSame(
            [
                'Enlight_Controller_Dispatcher_ControllerPath_Backend_ConnectGateway' => 'onGetControllerPathGateway',
                'Enlight_Controller_Dispatcher_ControllerPath_Frontend_Connect' => 'onGetControllerPathFrontend',
                'Enlight_Controller_Dispatcher_ControllerPath_Frontend_ConnectProductGateway' => 'onGetControllerPathFrontendConnectControllerGateway',
                'Enlight_Controller_Dispatcher_ControllerPath_Backend_Connect' => 'onGetControllerPathBackend',
                'Enlight_Controller_Dispatcher_ControllerPath_Backend_LastChanges' => 'onGetLastChangesControllerPath',
                'Enlight_Controller_Dispatcher_ControllerPath_Backend_ConnectConfig' => 'onGetControllerPathConnectConfig',
                'Enlight_Controller_Dispatcher_ControllerPath_Backend_Import' => 'onGetControllerPathImport'
            ],
            ControllerPath::getSubscribedEvents()
        );
    }
}
