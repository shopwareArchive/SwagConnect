<?php

namespace ShopwarePlugins\Connect\Subscribers;

use Shopware\Components\Model\ModelManager;
use ShopwarePlugins\Connect\Components\ProductStream\ProductStreamRepository;
use ShopwarePlugins\Connect\Components\ProductStream\ProductStreamService;
use Enlight\Event\SubscriberInterface;


class ServiceContainer extends BaseSubscriber
{
    private $manager;

    public function __construct(ModelManager $manager)
    {
        parent::__construct();
        $this->manager = $manager;
    }

    public function getSubscribedEvents()
    {
        return array(
            'Enlight_Bootstrap_InitResource_swagconnect.product_stream_service' => 'onProductStreamService',
        );
    }

    /**
     * @return ProductStreamService
     */
    public function onProductStreamService()
    {
        $productStreamQuery = new ProductStreamRepository($this->manager);

        return new ProductStreamService($productStreamQuery);
    }

}