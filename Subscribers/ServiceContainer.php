<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Subscribers;

use Shopware\Components\Model\ModelManager;
use Shopware\Connect\Gateway\PDO;
use Shopware\CustomModels\Connect\PaymentRepository;
use Shopware\CustomModels\Connect\ProductToRemoteCategory;
use Shopware\CustomModels\Connect\RemoteCategory;
use ShopwarePlugins\Connect\Components\Api\Request\RestApiRequest;
use ShopwarePlugins\Connect\Components\CategoryExtractor;
use ShopwarePlugins\Connect\Components\CategoryResolver\AutoCategoryResolver;
use ShopwarePlugins\Connect\Components\ConfigFactory;
use ShopwarePlugins\Connect\Components\FrontendQuery\FrontendQuery;
use ShopwarePlugins\Connect\Components\ImportService;
use ShopwarePlugins\Connect\Components\ProductStream\ProductStreamRepository;
use ShopwarePlugins\Connect\Components\ProductStream\ProductStreamService;
use Shopware\CustomModels\Connect\ProductStreamAttributeRepository;
use ShopwarePlugins\Connect\Components\RandomStringGenerator;
use ShopwarePlugins\Connect\Services\MenuService;
use ShopwarePlugins\Connect\Services\PaymentService;
use Shopware\Components\DependencyInjection\Container;
use Shopware\Models\Category\Category as CategoryModel;
use Shopware\Models\Article\Article as ArticleModel;
use Shopware\CustomModels\Connect\Attribute as ConnectAttribute;
use Enlight_Components_Db_Adapter_Pdo_Mysql;

class ServiceContainer extends BaseSubscriber
{
    /** @var ModelManager */
    private $manager;

    /** @var Enlight_Components_Db_Adapter_Pdo_Mysql */
    private $db;

    /** @var Container */
    private $container;

    /**
     * ServiceContainer constructor.
     * @param ModelManager $manager
     * @param Enlight_Components_Db_Adapter_Pdo_Mysql $db
     * @param Container $container
     */
    public function __construct(
        ModelManager $manager,
        Enlight_Components_Db_Adapter_Pdo_Mysql $db,
        Container $container
    ) {
        parent::__construct();
        $this->manager = $manager;
        $this->db = $db;
        $this->container = $container;
    }

    public function getSubscribedEvents()
    {
        return [
            'Enlight_Bootstrap_InitResource_swagconnect.product_stream_service' => 'onProductStreamService',
            'Enlight_Bootstrap_InitResource_swagconnect.payment_service' => 'onPaymentService',
            'Enlight_Bootstrap_InitResource_swagconnect.menu_service' => 'onMenuService',
            'Enlight_Bootstrap_InitResource_swagconnect.frontend_query' => 'onCreateFrontendQuery',
            'Enlight_Bootstrap_InitResource_swagconnect.rest_api_request' => 'onRestApiRequest',
            'Enlight_Bootstrap_InitResource_swagconnect.import_service' => 'onImportService',
            'Enlight_Bootstrap_InitResource_swagconnect.auto_category_reverter' => 'onAutoCategoryReverter',
        ];
    }

    /**
     * @return ProductStreamService
     */
    public function onProductStreamService()
    {
        /** @var ProductStreamAttributeRepository $streamAttrRepository */
        $streamAttrRepository = $this->manager->getRepository('Shopware\CustomModels\Connect\ProductStreamAttribute');

        return new ProductStreamService(
            new ProductStreamRepository($this->manager, $this->container->get('shopware_product_stream.repository')),
            $streamAttrRepository,
            ConfigFactory::getConfigInstance(),
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

    public function onRestApiRequest()
    {
        return new RestApiRequest(
            ConfigFactory::getConfigInstance()
        );
    }

    /**
     * @return \ShopwarePlugins\Connect\Components\ImportService
     */
    public function onImportService()
    {
        $autoCategoryResolver = new AutoCategoryResolver(
            $this->manager,
            $this->manager->getRepository(CategoryModel::class),
            $this->manager->getRepository(RemoteCategory::class),
            ConfigFactory::getConfigInstance()
        );

        return new ImportService(
            $this->manager,
            $this->container->get('multi_edit.product'),
            $this->manager->getRepository(CategoryModel::class),
            $this->manager->getRepository(ArticleModel::class),
            $this->manager->getRepository(RemoteCategory::class),
            $this->manager->getRepository(ProductToRemoteCategory::class),
            $autoCategoryResolver,
            new CategoryExtractor(
                $this->manager->getRepository(ConnectAttribute::class),
                $autoCategoryResolver,
                new PDO($this->db->getConnection()),
                new RandomStringGenerator(),
                $this->db
            ),
            $this->container->get('CategoryDenormalization'),
            $this->container->get('shopware_attribute.data_persister')
        );
    }

    /**
     * @return \ShopwarePlugins\Connect\Components\AutoCategoryReverter
     */
    public function onAutoCategoryReverter()
    {
        return new \ShopwarePlugins\Connect\Components\AutoCategoryReverter(
            $this->container->get('swagconnect.import_service')
        );
    }
}
