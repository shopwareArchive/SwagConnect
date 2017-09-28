<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Tests\Unit\Subscribers;

use Enlight\Event\SubscriberInterface;
use ShopwarePlugins\Connect\Subscribers\ArticleList;
use ShopwarePlugins\Connect\Tests\AbstractConnectUnitTest;
use Enlight_Components_Db_Adapter_Pdo_Mysql;

class ArticleListTest extends AbstractConnectUnitTest
{
    public function test_it_can_be_created()
    {
        $subscriber = new ArticleList(
            $this->createMock(Enlight_Components_Db_Adapter_Pdo_Mysql::class)
        );

        $this->assertInstanceOf(SubscriberInterface::class, $subscriber);
        $this->assertInstanceOf(ArticleList::class, $subscriber);
    }

    public function test_subscribed_events()
    {
        $this->assertSame(
            [
                'Shopware_Controllers_Backend_ArticleList_SQLParts' => 'onFilterArticle',
                'Enlight_Controller_Action_PostDispatch_Backend_ArticleList' => 'extendBackendArticleList'
            ],
            ArticleList::getSubscribedEvents()
        );
    }
}
