<?php

namespace ShopwarePlugins\Connect\Subscribers;

use Shopware\Components\Model\ModelManager;
use Shopware\CustomModels\Connect\PaymentRepository;
use ShopwarePlugins\Connect\Components\FrontendQuery\FrontendQuery;
use ShopwarePlugins\Connect\Components\ProductStream\ProductStreamRepository;
use ShopwarePlugins\Connect\Components\ProductStream\ProductStreamService;
use Enlight\Event\SubscriberInterface;
use Shopware\CustomModels\Connect\ProductStreamAttributeRepository;
use ShopwarePlugins\Connect\Services\MenuService;
use ShopwarePlugins\Connect\Services\PaymentService;
use Shopware\Components\DependencyInjection\Container;
use ShopwarePlugins\Connect\Components\Config;

class ServiceContainer extends BaseSubscriber
{
    /** @var ModelManager  */
    private $manager;

    /** @var Container */
    private $container;

    /**
     * ServiceContainer constructor.
     * @param ModelManager $manager
     * @param Container $container
     */
    public function __construct(
        ModelManager $manager,
        Container $container
    ) {
        parent::__construct();
        $this->manager = $manager;
        $this->container = $container;
    }

    public function getSubscribedEvents()
    {
        return array(
            'Enlight_Bootstrap_InitResource_swagconnect.product_stream_service' => 'onProductStreamService',
            'Enlight_Bootstrap_InitResource_swagconnect.payment_service' => 'onPaymentService',
            'Enlight_Bootstrap_InitResource_swagconnect.menu_service' => 'onMenuService',
            'Enlight_Bootstrap_InitResource_swagconnect.frontend_query' => 'onCreateFrontendQuery',
        );
    }

    /**
     * @return ProductStreamService
     */
    public function onProductStreamService()
    {
        /** @var ProductStreamAttributeRepository $streamAttrRepository */
        $streamAttrRepository = $this->manager->getRepository('Shopware\CustomModels\Connect\ProductStreamAttribute');

        return new ProductStreamService(
            new ProductStreamRepository($this->manager),
            $streamAttrRepository,
            new Config($this->manager),
            $this->container->get('shopware_search.product_search'),
            $this->container->get('shopware_storefront.context_service')
        );
    }

    /**
     * @return MenuService
     */
    public function onMenuService()
    {
        return new MenuService(
            $this->container->get('shopware_plugininstaller.plugin_manager'),
            $this->manager
        );
    }

    /**
     * @return PaymentService
     */
    public function onPaymentService()
    {
        return new PaymentService(
            $this->manager->getRepository('Shopware\Models\Payment\Payment'),
            new PaymentRepository($this->manager)
        );
    }

    public function onCreateFrontendQuery()
    {
        return new FrontendQuery($this->manager);
    }
}