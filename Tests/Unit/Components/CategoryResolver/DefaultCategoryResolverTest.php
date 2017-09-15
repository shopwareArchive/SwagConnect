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
use Shopware\Models\Category\Category;
use Shopware\Models\Category\Repository as CategoryRepository;
use ShopwarePlugins\Connect\Components\CategoryResolver\DefaultCategoryResolver;
use ShopwarePlugins\Connect\Tests\AbstractConnectUnitTest;
use ShopwarePlugins\Connect\Tests\Unit\Builders\RemoteCategoryBuilder;

class DefaultCategoryResolverTest extends AbstractConnectUnitTest
{
    use RemoteCategoryBuilder;

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
     * @var ProductToRemoteCategoryRepository
     */
    private $productToRemoteCategoryRepository;

    /**
     * @var CategoryRepository
     */
    private $categoryReposyitory;

    /**
     * @before
     */
    public function prepareMocks()
    {
        $this->remoteCategoryRepository = $this->createMock(RemoteCategoryRepository::class);
        $this->manager = $this->createMock(ModelManager::class);
        $this->productToRemoteCategoryRepository = $this->createMock(ProductToRemoteCategoryRepository::class);
        $this->categoryReposyitory = $this->createMock(CategoryRepository::class);

        $this->defaultCategoryResolver = new DefaultCategoryResolver(
            $this->manager,
            $this->remoteCategoryRepository,
            $this->productToRemoteCategoryRepository,
            $this->categoryReposyitory
        );
    }

    public function testResolveCategories()
    {
        $localCategory = new Category();

        $germanCategory = $this->newRemoteCategory(3)->buildRemoteCategory();
        $germanCategory->setCategoryKey('/deutsch');
        $germanCategory->addLocalCategory($localCategory);

        $bookCategory = $this->newRemoteCategory(4)->buildRemoteCategory();
        $bookCategory->setCategoryKey('/deutsch/buecher');

        $this->remoteCategoryRepository
            ->method('findBy')
            ->with(['categoryKey' => [$germanCategory->getCategoryKey(), $bookCategory->getCategoryKey()]])
            ->willReturn([$germanCategory, $bookCategory]);

        $result = $this->defaultCategoryResolver->resolve([$germanCategory->getCategoryKey() => null, $bookCategory->getCategoryKey() => null]);
        $this->assertCount(1, $result);
        $this->assertEquals($localCategory, $result[0]);
    }
}
