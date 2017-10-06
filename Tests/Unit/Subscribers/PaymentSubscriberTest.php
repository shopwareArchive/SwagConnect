<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Tests\Unit\Subscribers;

use Enlight\Event\SubscriberInterface;
use ShopwarePlugins\Connect\Subscribers\PaymentSubscriber;
use ShopwarePlugins\Connect\Tests\AbstractConnectUnitTest;
use ShopwarePlugins\Connect\Components\Helper;
use Shopware\Models\Payment\Repository as PaymentRepository;

class PaymentSubscriberTest extends AbstractConnectUnitTest
{
    public function test_it_can_be_created()
    {
        $subscriber = new PaymentSubscriber(
            $this->createMock(Helper::class),
            $this->createMock(PaymentRepository::class)
        );

        $this->assertInstanceOf(SubscriberInterface::class, $subscriber);
        $this->assertInstanceOf(PaymentSubscriber::class, $subscriber);
    }

    public function test_subscribed_events()
    {
        $this->assertSame(
            [
                'Enlight_Controller_Action_PostDispatch_Backend_Payment' => 'extendBackendPayment',
                'Shopware_Modules_Admin_GetPaymentMeans_DataFilter' => 'onFilterPaymentMethods',
            ],
            PaymentSubscriber::getSubscribedEvents()
        );
    }
}
