<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Tests\Unit\Subscribers;

use ShopwarePlugins\Connect\Subscribers\Supplier;
use ShopwarePlugins\Connect\Tests\AbstractConnectUnitTest;
use Enlight\Event\SubscriberInterface;
use Doctrine\DBAL\Connection;

class SupplierTest extends AbstractConnectUnitTest
{
    public function test_it_can_be_created()
    {
        $subscriber = new Supplier($this->createMock(Connection::class));

        $this->assertInstanceOf(SubscriberInterface::class, $subscriber);
        $this->assertInstanceOf(Supplier::class, $subscriber);
    }

    public function test_subscribed_events()
    {
        $this->assertSame(
            [
                'Enlight_Controller_Action_PostDispatch_Backend_Supplier' => 'extentBackendSupplier',
            ],
            Supplier::getSubscribedEvents()
        );
    }
}
