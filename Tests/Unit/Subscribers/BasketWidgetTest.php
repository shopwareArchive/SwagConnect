<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Tests\Unit\Subscribers;

use ShopwarePlugins\Connect\Subscribers\BasketWidget;
use ShopwarePlugins\Connect\Tests\AbstractConnectUnitTest;
use ShopwarePlugins\Connect\Components\Helper;
use Enlight\Event\SubscriberInterface;
use ShopwarePlugins\Connect\Components\BasketHelper;

class BasketWidgetTest extends AbstractConnectUnitTest
{
    public function test_it_can_be_created()
    {
        $subscriber = new BasketWidget(
            $this->createMock(BasketHelper::class),
            $this->createMock(Helper::class)
        );

        $this->assertInstanceOf(SubscriberInterface::class, $subscriber);
        $this->assertInstanceOf(BasketWidget::class, $subscriber);
    }

    public function test_subscribed_events()
    {
        $this->assertSame(
            [
                'sBasket::sGetBasket::after' => 'storeBasketResultToSession',
                'Enlight_Controller_Action_PostDispatch_Widgets_Checkout' => 'fixBasketWidgetForConnect',
                'Enlight_Controller_Action_PostDispatch_Frontend_Checkout' => 'fixBasketWidgetForConnect'
            ],
            BasketWidget::getSubscribedEvents()
        );
    }
}
