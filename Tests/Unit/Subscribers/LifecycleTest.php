<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Tests\Unit\Subscribers;

use Enlight\Event\SubscriberInterface;
use ShopwarePlugins\Connect\Components\Config;
use ShopwarePlugins\Connect\Components\ConnectFactory;
use ShopwarePlugins\Connect\Subscribers\Article;
use Shopware\Components\Model\ModelManager;
use ShopwarePlugins\Connect\Components\ConnectExport;
use ShopwarePlugins\Connect\Components\Helper;
use ShopwarePlugins\Connect\Subscribers\Lifecycle;
use ShopwarePlugins\Connect\Tests\AbstractConnectUnitTest;

class LifecycleTest extends AbstractConnectUnitTest
{
    /**
     * @var Article
     */
    private $subscriber;

    /**
     * @var ModelManager
     */
    private $modelManager;

    /**
     * @var Helper
     */
    private $helper;

    /**
     * @var ConnectExport
     */
    private $connectExport;

    /**
     * @var Config
     */
    private $config;

    /**
     * @before
     */
    public function prepareMocks()
    {
        $this->modelManager = $this->createMock(ModelManager::class);
        $this->helper = $this->createMock(Helper::class);
        $this->connectExport = $this->createMock(ConnectExport::class);
        $this->config = $this->createMock(Config::class);
        $this->subscriber = new Lifecycle(
            $this->modelManager,
            $this->helper,
            (new ConnectFactory())->getSDK(),
            $this->config,
            $this->connectExport
        );
    }

    public function test_it_can_be_created()
    {
        $this->assertInstanceOf(SubscriberInterface::class, $this->subscriber);
        $this->assertInstanceOf(Lifecycle::class, $this->subscriber);
    }

    public function test_subscribed_events()
    {
        $this->assertSame(
            [
                'Shopware\Models\Article\Article::preUpdate' => 'onPreUpdate',
                'Shopware\Models\Article\Article::postPersist' => 'onUpdateArticle',
                'Shopware\Models\Article\Detail::postPersist' => 'onPersistDetail',
                'Shopware\Models\Article\Article::preRemove' => 'onDeleteArticle',
                'Shopware\Models\Article\Detail::preRemove' => 'onDeleteDetail',
                'Shopware\Models\Order\Order::postUpdate' => 'onUpdateOrder',
                'Shopware\Models\Shop\Shop::preRemove' => 'onDeleteShop',
            ],
            Lifecycle::getSubscribedEvents()
        );
    }
}
