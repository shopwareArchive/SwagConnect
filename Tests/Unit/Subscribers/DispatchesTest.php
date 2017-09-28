<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Tests\Unit\Subscribers;

use ShopwarePlugins\Connect\Subscribers\Dispatches;
use ShopwarePlugins\Connect\Tests\AbstractConnectUnitTest;
use ShopwarePlugins\Connect\Components\Helper;
use Enlight\Event\SubscriberInterface;

class DispatchesTest extends AbstractConnectUnitTest
{
    public function test_it_can_be_created()
    {
        $subscriber = new Dispatches($this->createMock(Helper::class));

        $this->assertInstanceOf(SubscriberInterface::class, $subscriber);
        $this->assertInstanceOf(Dispatches::class, $subscriber);
    }

    public function test_subscribed_events()
    {
        $this->assertSame(
            [
                'Enlight_Controller_Action_PostDispatch_Backend_Shipping' => 'onPostDispatchBackendShipping',
                'sAdmin::sGetPremiumDispatches::after' => 'onFilterDispatches',
            ],
            Dispatches::getSubscribedEvents()
        );
    }
}
