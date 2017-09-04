<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Tests\Unit\Components\CategoryResolver;

use Shopware\Components\Model\ModelManager;
use Shopware\CustomModels\Connect\ProductToRemoteCategoryRepository;
use Shopware\CustomModels\Connect\RemoteCategoryRepository;
use ShopwarePlugins\Connect\Components\CategoryResolver\DefaultCategoryResolver;
use ShopwarePlugins\Connect\Tests\AbstractConnectUnitTest;

class DefaultCategoryResolverTest extends AbstractConnectUnitTest
{
    /**
     * @var DefaultCategoryResolver
     */
    private $defaultCategoryResolver;

    /**
     * @var  RemoteCategoryRepository
     */
    private $remoteCategoryRepository;

    /**
     * @var ModelManager
     */
    private $manager;

    /**
     * @before
     */
    public function prepareMocks()
    {
        $this->remoteCategoryRepository = $this->createMock(RemoteCategoryRepository::class);
        $this->manager = $this->createMock(ModelManager::class);

        $this->defaultCategoryResolver = new DefaultCategoryResolver(
            $this->manager,
            $this->remoteCategoryRepository,
            $this->createMock(ProductToRemoteCategoryRepository::class)
        );
    }

    public function test_store_remote_categories_success()
    {
        $articleId = 4;
        $categories = ['Category 1'];

        $this->manager->expects($this->exactly(2))
            ->method('flush');

        $this->manager->expects($this->never())
            ->method('remove');

        $this->defaultCategoryResolver->storeRemoteCategories($categories, $articleId);
    }
}
