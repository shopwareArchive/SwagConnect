<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\ShopwarePlugins\Connect\Subscribers;

use Enlight\Event\SubscriberInterface;
use Shopware\Components\Model\ModelManager;
use ShopwarePlugins\Connect\Components\BasketHelper;
use ShopwarePlugins\Connect\Components\ConnectFactory;
use ShopwarePlugins\Connect\Components\Helper;
use ShopwarePlugins\Connect\Subscribers\Checkout;
use ShopwarePlugins\Connect\Tests\DatabaseTestCaseTrait;

class CheckoutTest extends \PHPUnit_Framework_TestCase
{
    use DatabaseTestCaseTrait;

    public function testCanBeCreated()
    {
        $subscriber = new Checkout(
            $this->createMock(ModelManager::class),
            $this->createMock(\Enlight_Event_EventManager::class),
            (new ConnectFactory())->getSDK(),
            $this->createMock(BasketHelper::class),
            $this->createMock(Helper::class)
        );

        $this->assertInstanceOf(Checkout::class, $subscriber);
        $this->assertInstanceOf(SubscriberInterface::class, $subscriber);
    }
}
