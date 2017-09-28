<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Tests\Unit\Subscribers;

use ShopwarePlugins\Connect\Subscribers\CustomerGroup;
use ShopwarePlugins\Connect\Tests\AbstractConnectUnitTest;
use Enlight\Event\SubscriberInterface;
use ShopwarePlugins\Connect\Components\Logger;
use Shopware\Components\Model\ModelManager;

class CustomerGroupTest extends AbstractConnectUnitTest
{
    public function test_it_can_be_created()
    {
        $subscriber = new CustomerGroup(
            $this->createMock(ModelManager::class),
            $this->createMock(Logger::class)
        );

        $this->assertInstanceOf(SubscriberInterface::class, $subscriber);
        $this->assertInstanceOf(CustomerGroup::class, $subscriber);
    }

    public function test_subscribed_events()
    {
        $this->assertSame(
            [
                'Enlight_Controller_Action_PreDispatch_Backend_Base' => 'filterCustomerGroup',
                'Shopware\Models\Customer\Repository::getCustomerGroupsQueryBuilder::after' => 'filterCustomerGroupFromQueryBuilder',
                'Shopware\Models\Customer\Repository::getCustomerGroupsWithoutIdsQueryBuilder::before' => 'addToWithoutIdsQueryBuilder'
            ],
            CustomerGroup::getSubscribedEvents()
        );
    }
}
