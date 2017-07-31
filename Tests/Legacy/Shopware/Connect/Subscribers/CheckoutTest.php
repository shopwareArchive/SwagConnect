<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\ShopwarePlugins\Connect\Subscribers;

use Shopware\Components\Model\ModelManager;
use ShopwarePlugins\Connect\Subscribers\BaseSubscriber;
use ShopwarePlugins\Connect\Subscribers\Checkout;
use ShopwarePlugins\Connect\Tests\DatabaseTestCaseTrait;

class CheckoutTest extends \PHPUnit_Framework_TestCase
{
    use DatabaseTestCaseTrait;

    public function testCanBeCreated()
    {
        $subscriber = new Checkout(
            $this->createMock(ModelManager::class),
            $this->createMock(\Enlight_Event_EventManager::class)
        );

        $this->assertInstanceOf(Checkout::class, $subscriber);
        $this->assertInstanceOf(BaseSubscriber::class, $subscriber);
    }
}
