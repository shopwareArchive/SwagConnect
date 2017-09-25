<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Components;

use Shopware\CustomModels\Connect\ProductToRemoteCategory;
use Shopware\CustomModels\Connect\RemoteCategory;
use Shopware\CustomModels\Connect\RemoteCategoryRepository;
use Shopware\Components\Model\ModelManager;
use Shopware\CustomModels\Connect\ProductToRemoteCategoryRepository;
use Shopware\Models\Category\Repository as CategoryRepository;
use Shopware\Models\Category\Category;

abstract class CategoryResolver
{
    /**
     * @var ModelManager
     */
    protected $manager;

    /**
     * @var \Shopware\CustomModels\Connect\RemoteCategoryRepository
     */
    protected $remoteCategoryRepository;

    /**
     * @var \Shopware\CustomModels\Connect\ProductToRemoteCategoryRepository
     */
    protected $productToRemoteCategoryRepository;

    /**
     * @var \Shopware\Models\Category\Repository
     */
    protected $categoryRepository;

    public function __construct(
        ModelManager $manager,
        RemoteCategoryRepository $remoteCategoryRepository,
        ProductToRemoteCategoryRepository $productToRemoteCategoryRepository,
        CategoryRepository $categoryRepository
    ) {
        $this->manager = $manager;
        $this->remoteCategoryRepository = $remoteCategoryRepository;
        $this->productToRemoteCategoryRepository = $productToRemoteCategoryRepository;
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * Returns array with category entities
     * if they don't exist will be created
     *
     * @param array $categories
     * @return \Shopware\Models\Category\Category[]
     */
    abstract public function resolve(array $categories);

    /**
     * Generates categories tree by given array of categories
     *
     * @param array $categories
     * @param string $idPrefix
     * @return array
     */
    abstract public function generateTree(array $categories, $idPrefix = '');

    /**
     * Stores raw Shopware Connect categories
     *
     * @param array $categories
     * @param int $articleId
     * @return void
     */
    public function storeRemoteCategories(array $categories, $articleId)
    {
        $remoteCategories = [];
        foreach ($categories as $categoryKey => $category) {
            $remoteCategory = $this->remoteCategoryRepository->findOneBy(['categoryKey' => $categoryKey]);
            if (!$remoteCategory) {
                $remoteCategory = new RemoteCategory();
                $remoteCategory->setCategoryKey($categoryKey);
            }
            $remoteCategory->setLabel($category);
            $this->manager->persist($remoteCategory);
            $remoteCategories[] = $remoteCategory;
        }

        $this->manager->flush();

        $this->removeProductsFromRemoteCategory($remoteCategories, $articleId);
        $this->addProductToRemoteCategory($remoteCategories, $articleId);

        $this->manager->flush();
    }

    /**
     * @param RemoteCategory[] $remoteCategories
     * @param $articleId
     */
    private function addProductToRemoteCategory($remoteCategories, $articleId)
    {
        $productToCategories = $this->productToRemoteCategoryRepository->getArticleRemoteCategoryIds($articleId);
        /** @var $remoteCategory \Shopware\CustomModels\Connect\RemoteCategory */
        foreach ($remoteCategories as $remoteCategory) {
            if (!in_array($remoteCategory->getId(), $productToCategories)) {
                $productToCategory = new ProductToRemoteCategory();
                $productToCategory->setArticleId($articleId);
                $productToCategory->setConnectCategory($remoteCategory);
                $this->manager->persist($productToCategory);
            }
        }
    }

    /**
     * @param \Shopware\CustomModels\Connect\RemoteCategory[] $assignedCategories
     * @param $articleId
     */
    private function removeProductsFromRemoteCategory(array $assignedCategories, $articleId)
    {
        $currentProductCategoryIds = $this->productToRemoteCategoryRepository->getArticleRemoteCategoryIds($articleId);

        $assignedCategoryIds = array_map(function (RemoteCategory $assignedCategory) {
            $assignedCategory->getId();
        }, $assignedCategories);

        /** @var int $currentProductCategoryId */
        foreach ($currentProductCategoryIds as $currentProductCategoryId) {
            if (!in_array($currentProductCategoryId, $assignedCategoryIds)) {
                $this->productToRemoteCategoryRepository->deleteByConnectCategoryId($currentProductCategoryId, $articleId);
            }
        }
    }

    /**
     * Loop through category tree and fetch ids
     *
     * @param array $node
     * @param int $parentId
     * @param bool $returnOnlyLeafs
     * @param array $categories
     * @return array
     */
    public function convertTreeToKeys(array $node, $parentId, $returnOnlyLeafs = true, $categories = [])
    {
        foreach ($node as $category) {
            $categoryId = $this->checkAndCreateLocalCategory($category, $parentId);

            if ((!$returnOnlyLeafs) || (empty($category['children']))) {
                $categories[] = [
                    'categoryKey' => $categoryId,
                    'parentId' => $parentId,
                    'remoteCategory' => $category['categoryId']
                ];
            }

            if (!empty($category['children'])) {
                $categories = $this->convertTreeToKeys($category['children'], $categoryId, $returnOnlyLeafs, $categories);
            }
        }

        return $categories;
    }

    /**
     * @param array $category
     * @param int $parentId
     * @return int
     */
    private function checkAndCreateLocalCategory($category, $parentId)
    {
        $categoryModel = $this->categoryRepository->findOneBy([
            'name' => $category['name'],
            'parentId' => $parentId
        ]);

        if (!$categoryModel) {
            $categoryModel = $this->convertNodeToEntity($category, $parentId);
        }

        return $categoryModel->getId();
    }

    /**
     * @param array $category
     * @param int $parentId
     * @return Category
     */
    public function convertNodeToEntity(array $category, $parentId)
    {
        $categoryModel = new Category();
        $categoryModel->fromArray($this->getCategoryData($category['name']));

        $parent = $this->categoryRepository->findOneBy([
            'id' => (int) $parentId
        ]);
        $categoryModel->setParent($parent);

        $this->manager->persist($categoryModel);

        $categoryAttribute = $categoryModel->getAttribute();
        $categoryAttribute->setConnectImportedCategory(true);
        $this->manager->persist($categoryAttribute);

        /** @var \Shopware\CustomModels\Connect\RemoteCategory $remoteCategory */
        $remoteCategory = $this->remoteCategoryRepository->findOneBy(['categoryKey' => $category['categoryId']]);
        if ($remoteCategory) {
            $remoteCategory->addLocalCategory($categoryModel);
            $this->manager->persist($remoteCategory);
        }

        $this->manager->flush();

        return $categoryModel;
    }

    /**
     * Generate category data array
     * it's used to create category and
     * attribute from array
     *
     * @param string $name
     * @return array
     */
    private function getCategoryData($name)
    {
        return [
            'name' => $name,
            'active' => true,
            'childrenCount' => 0,
            'text' => $name,
            'attribute' => [
                'id' => 0,
                'parent' => 0,
                'name' => 'Deutsch',
                'position' => 0,
                'active' => true,
                'childrenCount' => 0,
                'text' => '',
                'cls' => '',
                'leaf' => false,
                'allowDrag' => false,
                'parentId' => 0,
                'categoryId' => null,
                'attribute1' => null,
                'attribute2' => null,
                'attribute3' => null,
                'attribute4' => null,
                'attribute5' => null,
                'attribute6' => null,
            ],
        ];
    }
}
