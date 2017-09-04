<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Bootstrap;

use Enlight_Components_Db_Adapter_Pdo_Mysql;
use Shopware\Components\Model\ModelManager;
use Shopware\Connect\Gateway\PDO;
use Shopware\Connect\SDK;
use ShopwarePlugins\Connect\Components\Config;
use ShopwarePlugins\Connect\Components\ConnectFactory;
use ShopwarePlugins\Connect\Components\Helper;

use ShopwarePlugins\Connect\Components\Logger;
use ShopwarePlugins\Connect\Components\ProductStream\ProductStreamRepository;
use ShopwarePlugins\Connect\Components\ProductStream\ProductStreamService;
use ShopwarePlugins\Connect\Subscribers\Article;
use ShopwarePlugins\Connect\Subscribers\ArticleList;
use ShopwarePlugins\Connect\Subscribers\BasketWidget;
use ShopwarePlugins\Connect\Subscribers\Category;
use ShopwarePlugins\Connect\Subscribers\Checkout;
use ShopwarePlugins\Connect\Subscribers\Connect;
use ShopwarePlugins\Connect\Subscribers\ControllerPath;
use ShopwarePlugins\Connect\Subscribers\CronJob;
use ShopwarePlugins\Connect\Subscribers\CustomerGroup;
use ShopwarePlugins\Connect\Subscribers\DisableConnectInFrontend;
use ShopwarePlugins\Connect\Subscribers\Dispatches;
use ShopwarePlugins\Connect\Subscribers\Javascript;
use ShopwarePlugins\Connect\Subscribers\Less;
use ShopwarePlugins\Connect\Subscribers\Lifecycle;
use ShopwarePlugins\Connect\Subscribers\OrderDocument;
use ShopwarePlugins\Connect\Subscribers\PaymentSubscriber;
use ShopwarePlugins\Connect\Subscribers\ProductStreams;
use ShopwarePlugins\Connect\Subscribers\Property;
use ShopwarePlugins\Connect\Subscribers\Search;
use ShopwarePlugins\Connect\Subscribers\ServiceContainer;
use ShopwarePlugins\Connect\Subscribers\Supplier;
use ShopwarePlugins\Connect\Subscribers\TemplateExtension;
use ShopwarePlugins\Connect\Subscribers\Voucher;
use Symfony\Component\DependencyInjection\Container;
use Shopware\Models\Payment\Payment;

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

    public function registerSubscribers()
    {
        try {
            $verified = $this->config->getConfig('apiKeyVerified', false);
        } catch (\Exception $e) {
            // if the config table is not available, just assume, that the update
            // still needs to be installed
            $verified = false;
        }

        $subscribers = $this->getDefaultSubscribers();
        if ($verified) {
            $subscribers = array_merge($subscribers, $this->getVerifiedSubscribers());
        } else {
            $subscribers = array_merge($subscribers, $this->getNotVerifiedSubscribers());
        }

        foreach ($subscribers as $newSubscriber) {
            $this->eventManager->addSubscriber($newSubscriber);
        }

        $this->modelManager->getEventManager()->addEventListener(
            [\Doctrine\ORM\Events::onFlush, \Doctrine\ORM\Events::postFlush],
            $this
        );
    }

    /**
     * @return array
     */
    private function getDefaultSubscribers()
    {
        return [
            new Article(
                new PDO($this->db->getConnection()),
                $this->modelManager,
                $this->connectFactory->getConnectExport(),
                $this->helper,
                $this->config,
                $this->connectFactory->getSDK()
            ),
            new ArticleList($this->container->get('db')),
            new Category(
                $this->container->get('dbal_connection'),
                $this->createProductStreamService()
            ),
            new Connect(
                $this->config,
                $this->SDK,
                $this->container->get('snippets')
            ),
            new ControllerPath($this->pluginBootstrap->Path()),
            new CronJob(
                $this->SDK,
                $this->connectFactory->getConnectExport(),
                $this->config,
                $this->helper
            ),
            new CustomerGroup(
                $this->modelManager,
                new Logger(Shopware()->Db())
            ),
            $this->getLifecycleSubscriber(),
            new OrderDocument(),
            new PaymentSubscriber(
                $this->helper,
                $this->modelManager->getRepository(Payment::class)
            ),
            new ProductStreams(
                $this->connectFactory->getConnectExport(),
                $this->config,
                $this->helper,
                $this->SDK,
                $this->container->get('db')
            ),
            new Property($this->modelManager),
            new Search($this->modelManager),
            new ServiceContainer(
                $this->modelManager,
                $this->db,
                $this->container
            ),
            new Supplier($this->container->get('dbal_connection'))
        ];
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
                $this->helper,
                $this->SDK,
                $this->config,
                $this->connectFactory->getConnectExport()
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

    /**
     * @return ProductStreamService
     */
    private function createProductStreamService()
    {
        /** @var ProductStreamAttributeRepository $streamAttrRepository */
        $streamAttrRepository = $this->modelManager->getRepository('Shopware\CustomModels\Connect\ProductStreamAttribute');

        return new ProductStreamService(
            new ProductStreamRepository($this->modelManager, $this->container->get('shopware_product_stream.repository')),
            $streamAttrRepository,
            $this->config,
            $this->container->get('shopware_search.product_search'),
            $this->container->get('shopware_storefront.context_service')
        );
    }

    /**
     * @return array
     */
    private function getVerifiedSubscribers()
    {
        return [
            new BasketWidget(
                $this->pluginBootstrap->getBasketHelper(),
                $this->helper
            ),
            new Checkout(
                $this->modelManager,
                $this->eventManager,
                $this->connectFactory->getSDK(),
                $this->connectFactory->getBasketHelper(),
                $this->connectFactory->getHelper()
            ),
            new Dispatches($this->helper),
            new Javascript(),
            new Less()
        ];
    }

    /**
     * @return array
     */
    private function getNotVerifiedSubscribers()
    {
        return [
            new DisableConnectInFrontend(
                $this->container->get('db')
            ),
            new TemplateExtension(
                $this->SDK,
                $this->helper
            ),
            new Voucher(
                $this->helper,
                $this->connectFactory->getBasketHelper(),
                $this->container->get('snippets')
            )
        ];
    }
}
