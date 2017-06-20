<?php


namespace Tests\ShopwarePlugins\Connect\Subscribers;


use Shopware\Components\Model\ModelManager;
use ShopwarePlugins\Connect\Subscribers\BaseSubscriber;
use ShopwarePlugins\Connect\Subscribers\Checkout;
use Tests\ShopwarePlugins\Connect\DatabaseTestCaseTrait;

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
