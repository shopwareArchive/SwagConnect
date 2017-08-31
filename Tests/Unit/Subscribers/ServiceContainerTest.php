<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Tests\Unit\Subscribers;

use Shopware\CustomModels\Connect\ProductToRemoteCategory;
use Shopware\CustomModels\Connect\ProductToRemoteCategoryRepository;
use Shopware\CustomModels\Connect\RemoteCategory;
use Shopware\CustomModels\Connect\RemoteCategoryRepository;
use ShopwarePlugins\Connect\Components\Api\Request\RestApiRequest;
use ShopwarePlugins\Connect\Components\CategoryResolver\AutoCategoryResolver;
use ShopwarePlugins\Connect\Components\CategoryResolver\DefaultCategoryResolver;
use ShopwarePlugins\Connect\Components\FrontendQuery\FrontendQuery;
use ShopwarePlugins\Connect\Components\ImportService;
use ShopwarePlugins\Connect\Services\PaymentService;
use ShopwarePlugins\Connect\Subscribers\ServiceContainer;
use ShopwarePlugins\Connect\Tests\AbstractConnectUnitTest;
use Shopware\Components\Model\ModelManager;
use Enlight_Components_Db_Adapter_Pdo_Mysql;
use Shopware\Components\DependencyInjection\Container;
use Shopware\Models\Category\Category;
use Shopware\Models\Category\Repository as CategoryRepository;
use ShopwarePlugins\Connect\Components\AutoCategoryReverter;
use Shopware\Models\Payment\Repository as PaymentRepository;
use Shopware\Models\Payment\Payment;
use ShopwarePlugins\Connect\Services\MenuService;
use Shopware\Bundle\PluginInstallerBundle\Service\InstallerService;

class ServiceContainerTest extends AbstractConnectUnitTest
{
    /**
     * @var ServiceContainer
     */
    private $serviceContainer;

    /**
     * @var ModelManager
     */
    private $modelManager;

    /**
     * @var Container
     */
    private $diContainer;

    /**
     * @var Enlight_Components_Db_Adapter_Pdo_Mysql
     */
    private $db;

    /**
     * @before
     */
    public function prepareMocks()
    {
        $this->modelManager = $this->createMock(ModelManager::class);
        $this->diContainer = $this->createMock(Container::class);
        $this->db = $this->createMock(Enlight_Components_Db_Adapter_Pdo_Mysql::class);

        $this->serviceContainer = new ServiceContainer(
            $this->modelManager,
            $this->db,
            $this->diContainer
        );
    }

    public function testGetSubscribedEvents()
    {
        $this->assertSame(
            [
                'Enlight_Bootstrap_InitResource_swagconnect.product_stream_service' => 'onProductStreamService',
                'Enlight_Bootstrap_InitResource_swagconnect.payment_service' => 'onPaymentService',
                'Enlight_Bootstrap_InitResource_swagconnect.menu_service' => 'onMenuService',
                'Enlight_Bootstrap_InitResource_swagconnect.frontend_query' => 'onCreateFrontendQuery',
                'Enlight_Bootstrap_InitResource_swagconnect.rest_api_request' => 'onRestApiRequest',
                'Enlight_Bootstrap_InitResource_swagconnect.import_service' => 'onImportService',
                'Enlight_Bootstrap_InitResource_swagconnect.auto_category_reverter' => 'onAutoCategoryReverter',
                'Enlight_Bootstrap_InitResource_swagconnect.auto_category_resolver' => 'onAutoCategoryResolver',
                'Enlight_Bootstrap_InitResource_swagconnect.default_category_resolver' => 'onDefaultCategoryResolver',
            ],
            $this->serviceContainer->getSubscribedEvents()
        );
    }

    public function testOnAutoCategoryResolver()
    {
        $this->modelManager
            ->method('getRepository')
            ->will($this->returnValueMap([
                [Category::class, $this->createMock(CategoryRepository::class)],
                [RemoteCategory::class, $this->createMock(RemoteCategoryRepository::class)],
                [ProductToRemoteCategory::class, $this->createMock(ProductToRemoteCategoryRepository::class)],
            ]));

        $this->assertInstanceOf(AutoCategoryResolver::class, $this->serviceContainer->onAutoCategoryResolver());
    }

    public function testOnDefaultCategoryResolver()
    {
        $this->modelManager
            ->method('getRepository')
            ->will($this->returnValueMap([
                [RemoteCategory::class, $this->createMock(RemoteCategoryRepository::class)],
                [ProductToRemoteCategory::class, $this->createMock(ProductToRemoteCategoryRepository::class)],
            ]));

        $this->assertInstanceOf(DefaultCategoryResolver::class, $this->serviceContainer->onDefaultCategoryResolver());
    }

    public function testOnAutoCategoryReverter()
    {
        $this->diContainer
            ->expects($this->once())
            ->method('get')
            ->with('swagconnect.import_service')
            ->willReturn($this->createMock(ImportService::class));

        $this->assertInstanceOf(AutoCategoryReverter::class, $this->serviceContainer->onAutoCategoryReverter());
    }

    public function testOnRestApiRequest()
    {
        $this->assertInstanceOf(RestApiRequest::class, $this->serviceContainer->onRestApiRequest());
    }

    public function testOnCreateFrontendQuery()
    {
        $this->assertInstanceOf(FrontendQuery::class, $this->serviceContainer->onCreateFrontendQuery());
    }

    public function testOnPaymentService()
    {
        $this->modelManager
            ->expects($this->once())
            ->method('getRepository')
            ->with(Payment::class)
            ->willReturn($this->createMock(PaymentRepository::class));

        $this->assertInstanceOf(PaymentService::class, $this->serviceContainer->onPaymentService());
    }

    public function testOnMenuService()
    {
        $this->diContainer
            ->expects($this->once())
            ->method('get')
            ->with('shopware_plugininstaller.plugin_manager')
            ->willReturn($this->createMock(InstallerService::class));

        $this->assertInstanceOf(MenuService::class, $this->serviceContainer->onMenuService());
    }
}
