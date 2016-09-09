<?php

namespace ShopwarePlugins\Connect\Subscribers;

use Shopware\Components\Model\ModelManager;
use ShopwarePlugins\Connect\Components\FrontendQuery\FrontendQuery;
use ShopwarePlugins\Connect\Components\ProductStream\ProductStreamRepository;
use ShopwarePlugins\Connect\Components\ProductStream\ProductStreamService;
use Enlight\Event\SubscriberInterface;
use Shopware\CustomModels\Connect\ProductStreamAttributeRepository;

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
            'Enlight_Bootstrap_InitResource_swagconnect.frontend_query' => 'onCreateFrontendQuery',
        );
    }

    /**
     * @return ProductStreamService
     */
    public function onProductStreamService()
    {
        $productStreamQuery = new ProductStreamRepository($this->manager);

        /** @var ProductStreamAttributeRepository $streamAttrRepository */
        $streamAttrRepository = $this->manager->getRepository('Shopware\CustomModels\Connect\ProductStreamAttribute');

        return new ProductStreamService($productStreamQuery, $streamAttrRepository);
    }

    public function onCreateFrontendQuery()
    {
        return new FrontendQuery($this->manager);
    }
}