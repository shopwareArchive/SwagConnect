<?php

namespace ShopwarePlugins\Connect\Bootstrap;

use Enlight_Components_Db_Adapter_Pdo_Mysql;
use Shopware\Components\Model\ModelManager;
use Shopware\Connect\Gateway\PDO;
use Shopware\Connect\SDK;
use ShopwarePlugins\Connect\Components\Config;
use ShopwarePlugins\Connect\Components\ConnectFactory;
use ShopwarePlugins\Connect\Components\Helper;
use ShopwarePlugins\Connect\Subscribers\Checkout;
use ShopwarePlugins\Connect\Subscribers\Lifecycle;
use Symfony\Component\DependencyInjection\Container;
use ShopwarePlugins\Connect\Components\ProductStream\ProductStreamService;
use ShopwarePlugins\Connect\Components\ProductStream\ProductStreamRepository;
use Shopware\CustomModels\Connect\ProductStreamAttribute;

class SubscriberRegistration
{
    /**
     * @var ModelManager
     */
    private $modelManager;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var Enlight_Components_Db_Adapter_Pdo_Mysql
     */
    private $db;

    /**
     * @TODO: Subscribers should not depend on the Bootstrap class. If you see a possible solution refactor it please.
     *
     * @var \Shopware_Plugins_Backend_SwagConnect_Bootstrap
     */
    private $pluginBootstrap;

    /**
     * @var \Enlight_Event_EventManager
     */
    private $eventManager;

    /**
     * @var SDK
     */
    private $SDK;

    /**
     * @var ConnectFactory
     */
    private $connectFactory;

    /**
     * @var Helper
     */
    private $helper;

    /**
     * This property saves all product updates and will be inserted back later
     *
     * @var array
     */
    private $productUpdates = [];

    /**
     * @var Lifecycle
     */
    private $lifecycle;

    /**
     * @var Container
     */
    private $container;

    /**
     * @param Config $config
     * @param ModelManager $modelManager
     * @param Enlight_Components_Db_Adapter_Pdo_Mysql $db
     * @param \Shopware_Plugins_Backend_SwagConnect_Bootstrap $pluginBootstrap
     * @param \Enlight_Event_EventManager $eventManager
     * @param SDK $SDK
     * @param ConnectFactory $connectFactory
     * @param Helper $helper
     * @param Container $container
     */
    public function __construct(
        Config $config,
        ModelManager $modelManager,
        Enlight_Components_Db_Adapter_Pdo_Mysql $db,
        \Shopware_Plugins_Backend_SwagConnect_Bootstrap $pluginBootstrap,
        \Enlight_Event_EventManager $eventManager,
        SDK $SDK,
        ConnectFactory $connectFactory,
        Helper $helper,
        Container $container
    ) {
        $this->config = $config;
        $this->modelManager = $modelManager;
        $this->db = $db;
        $this->pluginBootstrap = $pluginBootstrap;
        $this->eventManager = $eventManager;
        $this->SDK = $SDK;
        $this->connectFactory = $connectFactory;
        $this->helper = $helper;
        $this->container = $container;
    }

    /**
     * @param boolean $isShopware52
     */
    public function registerSubscribers($isShopware52)
    {
        try {
            $verified = $this->config->getConfig('apiKeyVerified', false);
        } catch (\Exception $e) {
            // if the config table is not available, just assume, that the update
            // still needs to be installed
            $verified = false;
        }

        $subscribers = $this->getDefaultSubscribers($isShopware52);

        // Some subscribers may only be used, if the SDK is verified
        if ($verified) {
            $subscribers = array_merge($subscribers, $this->getSubscribersForVerifiedKeys());
            // These subscribers are used if the api key is not valid
        } else {
            $subscribers = array_merge($subscribers, $this->getSubscribersForUnverifiedKeys());
        }

        /** @var $subscriber \ShopwarePlugins\Connect\Subscribers\BaseSubscriber */
        foreach ($subscribers as $subscriber) {
            $subscriber->setBootstrap($this->pluginBootstrap);
            $this->eventManager->registerSubscriber($subscriber);
        }

        $this->modelManager->getEventManager()->addEventListener(
            [\Doctrine\ORM\Events::onFlush, \Doctrine\ORM\Events::postFlush],
            $this
        );
    }


    /**
     * Default subscribers can safely be used, even if the api key wasn't verified, yet
     *
     * @param bool $isShopware52
     * @return array
     */
    private function getDefaultSubscribers($isShopware52)
    {
        return [
            new \ShopwarePlugins\Connect\Subscribers\OrderDocument(),
            new \ShopwarePlugins\Connect\Subscribers\ControllerPath($isShopware52),
            new \ShopwarePlugins\Connect\Subscribers\CustomerGroup(),
            new \ShopwarePlugins\Connect\Subscribers\CronJob(
                $this->SDK,
                $this->connectFactory->getConnectExport()
            ),
            new \ShopwarePlugins\Connect\Subscribers\ArticleList(),
            new \ShopwarePlugins\Connect\Subscribers\Article(
                new PDO($this->db->getConnection()),
                $this->modelManager,
                $this->connectFactory->getConnectExport(),
                $this->helper,
                $this->config
            ),
            new \ShopwarePlugins\Connect\Subscribers\Category(
                $this->modelManager
            ),
            new \ShopwarePlugins\Connect\Subscribers\Connect(),
            new \ShopwarePlugins\Connect\Subscribers\Payment(),
            new \ShopwarePlugins\Connect\Subscribers\ServiceContainer(
                $this->modelManager,
                $this->db,
                $this->container
            ),
            new \ShopwarePlugins\Connect\Subscribers\Supplier(),
            new \ShopwarePlugins\Connect\Subscribers\ProductStreams(
                $this->connectFactory->getConnectExport(),
                new Config($this->modelManager),
                $this->helper
            ),
            new \ShopwarePlugins\Connect\Subscribers\Property(
                $this->modelManager
            ),
            new \ShopwarePlugins\Connect\Subscribers\Search(
                $this->modelManager
            ),
        ];
    }

    /**
     * @return array
     */
    private function getSubscribersForUnverifiedKeys()
    {
        return [
            new \ShopwarePlugins\Connect\Subscribers\DisableConnectInFrontend(),
            $this->getLifecycleSubscriber()
        ];
    }

    /**
     * These subscribers will only be used, once the user has verified his api key
     * This will prevent the users from having shopware Connect extensions in their frontend
     * even if they cannot use shopware Connect due to the missing / wrong api key
     *
     * @return array
     */
    private function getSubscribersForVerifiedKeys()
    {
        $subscribers = [
            new \ShopwarePlugins\Connect\Subscribers\TemplateExtension(),
            $this->createCheckoutSubscriber(),
            new \ShopwarePlugins\Connect\Subscribers\Voucher(),
            new \ShopwarePlugins\Connect\Subscribers\BasketWidget(),
            new \ShopwarePlugins\Connect\Subscribers\Dispatches(),
            new \ShopwarePlugins\Connect\Subscribers\Javascript(),
            new \ShopwarePlugins\Connect\Subscribers\Less(),
            $this->getLifecycleSubscriber()

        ];

        return $subscribers;
    }


    /**
     * Creates checkout subscriber
     *
     * @return Checkout
     */
    private function createCheckoutSubscriber()
    {
        $checkoutSubscriber = new Checkout(
            $this->modelManager,
            $this->eventManager
        );
        foreach ($checkoutSubscriber->getListeners() as $listener) {
            if ($listener->getName() === 'Enlight_Controller_Action_PostDispatch_Frontend_Checkout') {
                $listener->setPosition(-1);
            }
        }

        return $checkoutSubscriber;
    }

    /**
     * Generate changes for updated Articles and Details.
     * On postFlush all related entities are updated and product can
     * be fetched from DB correctly.
     *
     * @param \Doctrine\ORM\Event\PostFlushEventArgs $eventArgs
     */
    public function postFlush(\Doctrine\ORM\Event\PostFlushEventArgs $eventArgs)
    {
        foreach ($this->productUpdates as $entity) {
            $this->getLifecycleSubscriber()->handleChange($entity);
        }

        $this->productUpdates = [];
    }

    /**
     * @return Lifecycle
     */
    private function getLifecycleSubscriber()
    {
        if (!$this->lifecycle) {
            $this->lifecycle = new Lifecycle(
                $this->modelManager,
                $this->config->getConfig('autoUpdateProducts', 1)
            );
        }

        return $this->lifecycle;
    }


    /**
     * Collect updated Articles and Details
     * Lifecycle events don't work correctly, because products will be fetched via query builder,
     * but related entities like price are not updated yet.
     *
     * @param \Doctrine\ORM\Event\OnFlushEventArgs $eventArgs
     */
    public function onFlush(\Doctrine\ORM\Event\OnFlushEventArgs $eventArgs)
    {
        /** @var $em ModelManager */
        $em  = $eventArgs->getEntityManager();
        $uow = $em->getUnitOfWork();

        // Entity updates
        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            if (!$entity instanceof \Shopware\Models\Article\Article
                && !$entity instanceof \Shopware\Models\Article\Detail
            ) {
                continue;
            }

            $this->productUpdates[] = $entity;
        }
    }
}