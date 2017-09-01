<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Tests\Unit\Subscribers;

use ShopwarePlugins\Connect\Tests\AbstractConnectUnitTest;
use Enlight\Event\SubscriberInterface;
use ShopwarePlugins\Connect\Subscribers\Category;
use Doctrine\DBAL\Connection;
use ShopwarePlugins\Connect\Components\ProductStream\ProductStreamService;

class CategoryTest extends AbstractConnectUnitTest
{
    public function test_it_can_be_created()
    {
        $subscriber = new Category(
            $this->createMock(Connection::class),
            $this->createMock(ProductStreamService::class)
        );

        $this->assertInstanceOf(SubscriberInterface::class, $subscriber);
        $this->assertInstanceOf(Category::class, $subscriber);
    }

    public function test_subscribed_events()
    {
        $this->assertSame(
            [
                'Enlight_Controller_Action_PostDispatch_Backend_Category' => 'extendBackendCategory'
            ],
            Category::getSubscribedEvents()
        );
    }
}
