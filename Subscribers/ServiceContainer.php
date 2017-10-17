<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Subscribers;

use Enlight\Event\SubscriberInterface;
use Shopware\Components\Model\ModelManager;
use Shopware\Connect\Gateway\PDO;
use Shopware\CustomModels\Connect\PaymentRepository;
use Shopware\CustomModels\Connect\ProductToRemoteCategory;
use Shopware\CustomModels\Connect\RemoteCategory;
use ShopwarePlugins\Connect\Components\Api\Request\RestApiRequest;
use ShopwarePlugins\Connect\Components\CategoryExtractor;
use ShopwarePlugins\Connect\Components\CategoryResolver\AutoCategoryResolver;
use ShopwarePlugins\Connect\Components\Config;
use ShopwarePlugins\Connect\Components\CategoryResolver\DefaultCategoryResolver;
use ShopwarePlugins\Connect\Components\FrontendQuery\FrontendQuery;
use ShopwarePlugins\Connect\Components\ImportService;
use ShopwarePlugins\Connect\Components\ProductStream\ProductSearch;
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
use Shopware\CustomModels\Connect\ProductStreamAttribute;

class ServiceContainer implements SubscriberInterface
{
    /**
     * @var ModelManager
     */
    private $manager;

    /**
     * @var Enlight_Components_Db_Adapter_Pdo_Mysql
     */
    private $db;

    /**
     * @var Container
     */
    private $container;

    /** @var Config */
    private $config;

    /**
     * ServiceContainer constructor.
     * @param ModelManager $manager
     * @param Enlight_Components_Db_Adapter_Pdo_Mysql $db
     * @param Container $container
     * @param Config $config
     */
    public function __construct(
        ModelManager $manager,
        Enlight_Components_Db_Adapter_Pdo_Mysql $db,
        Container $container,
        Config $config
    ) {
        $this->manager = $manager;
        $this->db = $db;
        $this->container = $container;
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Bootstrap_InitResource_swagconnect.product_stream_service' => 'onProductStreamService',
            'Enlight_Bootstrap_InitResource_swagconnect.product_search' => 'onProductSearch',
            'Enlight_Bootstrap_InitResource_swagconnect.payment_service' => 'onPaymentService',
            'Enlight_Bootstrap_InitResource_swagconnect.menu_service' => 'onMenuService',
            'Enlight_Bootstrap_InitResource_swagconnect.frontend_query' => 'onCreateFrontendQuery',
            'Enlight_Bootstrap_InitResource_swagconnect.rest_api_request' => 'onRestApiRequest',
            'Enlight_Bootstrap_InitResource_swagconnect.import_service' => 'onImportService',
            'Enlight_Bootstrap_InitResource_swagconnect.auto_category_reverter' => 'onAutoCategoryReverter',
            'Enlight_Bootstrap_InitResource_swagconnect.auto_category_resolver' => 'onAutoCategoryResolver',
            'Enlight_Bootstrap_InitResource_swagconnect.default_category_resolver' => 'onDefaultCategoryResolver',
        ];
    }

    /**
     * @return ProductStreamService
     */
    public function onProductStreamService()
    {
        /** @var ProductStreamAttributeRepository $streamAttrRepository */
        $streamAttrRepository = $this->manager->getRepository(ProductStreamAttribute::class);

        return new ProductStreamService(
            new ProductStreamRepository($this->manager, $this->container->get('shopware_product_stream.repository')),
            $streamAttrRepository,
            $this->config
        );
    }

    /**
     * @return ProductSearch
     */
    public function onProductSearch()
    {
        return new ProductSearch(
            $this->container->get('shopware_product_stream.repository'),
            $this->config,
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

    /**
     * @return FrontendQuery
     */
    public function onCreateFrontendQuery()
    {
        return new FrontendQuery($this->manager);
    }

    /**
     * @return RestApiRequest
     */
    public function onRestApiRequest()
    {
        return new RestApiRequest($this->config);
    }

    /**
     * @return \ShopwarePlugins\Connect\Components\ImportService
     */
    public function onImportService()
    {
        return new ImportService(
            $this->manager,
            $this->container->get('multi_edit.product'),
            $this->manager->getRepository(CategoryModel::class),
            $this->manager->getRepository(ArticleModel::class),
            $this->manager->getRepository(RemoteCategory::class),
            $this->manager->getRepository(ProductToRemoteCategory::class),
            $this->container->get('swagconnect.auto_category_resolver'),
            new CategoryExtractor(
                $this->manager->getRepository(ConnectAttribute::class),
                $this->container->get('swagconnect.auto_category_resolver'),
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

    /**
     * @return \ShopwarePlugins\Connect\Components\CategoryResolver\AutoCategoryResolver
     */
    public function onAutoCategoryResolver()
    {
        return new AutoCategoryResolver(
            $this->manager,
            $this->manager->getRepository(CategoryModel::class),
            $this->manager->getRepository(RemoteCategory::class),
            $this->config,
            $this->manager->getRepository(ProductToRemoteCategory::class)
        );
    }

    /**
     * @return \ShopwarePlugins\Connect\Components\CategoryResolver\DefaultCategoryResolver
     */
    public function onDefaultCategoryResolver()
    {
        return new DefaultCategoryResolver(
            $this->manager,
            $this->manager->getRepository(RemoteCategory::class),
            $this->manager->getRepository(ProductToRemoteCategory::class),
            $this->manager->getRepository(CategoryModel::class)
        );
    }
}
