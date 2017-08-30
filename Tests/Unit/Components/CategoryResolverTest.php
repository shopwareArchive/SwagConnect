<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Tests\Unit\Components;

use Shopware\CustomModels\Connect\ProductToRemoteCategory;
use Shopware\CustomModels\Connect\ProductToRemoteCategoryRepository;
use Shopware\CustomModels\Connect\RemoteCategoryRepository;
use ShopwarePlugins\Connect\Components\CategoryResolver;
use ShopwarePlugins\Connect\Tests\AbstractConnectUnitTest;
use Shopware\Components\Model\ModelManager;
use ShopwarePlugins\Connect\Tests\Unit\Builders\RemoteCategoryBuilder;
use ShopwarePlugins\Connect\Tests\UnitTestCaseTrait;

class CategoryResolverTest extends AbstractConnectUnitTest
{
    use UnitTestCaseTrait;

    use RemoteCategoryBuilder;

    /**
     * @var CategoryResolver
     */
    private $categoryResolver;

    /**
     * @var ModelManager
     */
    private $modelManager;

    /**
     * @var RemoteCategoryRepository
     */
    private $remoteCategoryRepository;

    /**
     * @var ProductToRemoteCategoryRepository
     */
    private $remoteProductToCategoryRepository;

    /**
     * @before
     */
    public function prepareMocks()
    {
        $this->modelManager = $this->createMock(ModelManager::class);
        $this->remoteCategoryRepository = $this->createMock(RemoteCategoryRepository::class);
        $this->remoteProductToCategoryRepository = $this->createMock(ProductToRemoteCategoryRepository::class);

        $this->categoryResolver = $this->getMockForAbstractClass(
            CategoryResolver::class,
        [
            $this->modelManager,
            $this->remoteCategoryRepository,
            $this->remoteProductToCategoryRepository
        ]
        );
    }

    public function testStoreRemoteCategories()
    {
        $articleId = 5;
        $germanCategory = $this->newRemoteCategory(3)->buildRemoteCategory();
        $bookCategory = $this->newRemoteCategory(4)->buildRemoteCategory();

        $this->modelManager->expects($this->exactly(2))->method('flush');

        $this->remoteCategoryRepository
            ->expects($this->at(0))
            ->method('findOneBy')
            ->with(['categoryKey' => '/deutsch'])
            ->willReturn($germanCategory);

        $this->remoteCategoryRepository
            ->expects($this->at(1))
            ->method('findOneBy')
            ->with(['categoryKey' => '/deutsch/buecher'])
            ->willReturn($bookCategory);

        $this->modelManager->expects($this->exactly(4))->method('persist');

        $productToGermanCategory = new ProductToRemoteCategory();
        $productToGermanCategory->setArticleId($articleId);
        $productToGermanCategory->setConnectCategory($germanCategory);
        $this->remoteProductToCategoryRepository
            ->expects($this->at(0))
            ->method('findOneBy')
            ->with(['articleId' => $articleId, 'connectCategoryId' => $germanCategory->getId()])
            ->willReturn($productToGermanCategory);
        $productToBookCategory = new ProductToRemoteCategory();
        $productToBookCategory->setArticleId($articleId);
        $productToBookCategory->setConnectCategory($bookCategory);
        $this->remoteProductToCategoryRepository
            ->expects($this->at(1))
            ->method('findOneBy')
            ->with(['articleId' => $articleId, 'connectCategoryId' => $bookCategory->getId()])
            ->willReturn($productToBookCategory);

        $this->modelManager->expects($this->any())->method('persist')->with($this->captureAllArg($persistArgs));

        $this->categoryResolver->storeRemoteCategories(
            [
                '/deutsch' => 'Deutsch',
                '/deutsch/buecher' => 'Buecher',
                '/deutsch/buecher/fantasy' => 'Fantasy'
            ],
            $articleId
        );

        $fantasyCategory = $persistArgs[2];
        $productToFantasyCategory = $persistArgs[3];

        $this->assertEquals('Deutsch', $germanCategory->getLabel());
        $this->assertEquals('Buecher', $bookCategory->getLabel());
        $this->assertEquals('Fantasy', $fantasyCategory->getLabel());

        $this->assertEquals($articleId, $productToBookCategory->getArticleId());
        $this->assertEquals($articleId, $productToFantasyCategory->getArticleId());

        $this->assertEquals($germanCategory, $productToGermanCategory->getConnectCategory());
        $this->assertEquals($bookCategory, $productToBookCategory->getConnectCategory());
        $this->assertEquals($fantasyCategory, $productToFantasyCategory->getConnectCategory());
    }
}
