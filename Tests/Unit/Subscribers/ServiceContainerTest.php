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
use ShopwarePlugins\Connect\Components\CategoryResolver\AutoCategoryResolver;
use ShopwarePlugins\Connect\Components\CategoryResolver\DefaultCategoryResolver;
use ShopwarePlugins\Connect\Subscribers\ServiceContainer;
use ShopwarePlugins\Connect\Tests\AbstractConnectUnitTest;
use Shopware\Components\Model\ModelManager;
use Enlight_Components_Db_Adapter_Pdo_Mysql;
use Shopware\Components\DependencyInjection\Container;
use Shopware\Models\Category\Category;
use Shopware\Models\Category\Repository as CategoryRepository;

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
     * @before
     */
    public function prepareMocks()
    {
        $this->modelManager = $this->createMock(ModelManager::class);

        $this->serviceContainer = new ServiceContainer(
            $this->modelManager,
            $this->createMock(Enlight_Components_Db_Adapter_Pdo_Mysql::class),
            $this->createMock(Container::class)
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
}
