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
use Shopware\Components\Model\CategoryDenormalization;

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

    /**
     * @var CategoryDenormalization
     */
    private $categoryDenormalization;

    public function __construct(
        ModelManager $manager,
        RemoteCategoryRepository $remoteCategoryRepository,
        ProductToRemoteCategoryRepository $productToRemoteCategoryRepository,
        CategoryRepository $categoryRepository,
        CategoryDenormalization $categoryDenormalization
    ) {
        $this->manager = $manager;
        $this->remoteCategoryRepository = $remoteCategoryRepository;
        $this->productToRemoteCategoryRepository = $productToRemoteCategoryRepository;
        $this->categoryRepository = $categoryRepository;
        $this->categoryDenormalization = $categoryDenormalization;
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

        $this->removeProductsFromNotAssignedRemoteCategories($remoteCategories, $articleId);
        $this->addProductToRemoteCategory($remoteCategories, $articleId);

        $this->manager->flush();
    }

    /**
     * @param RemoteCategory[] $remoteCategories
     * @param $articleId
     */
    private function addProductToRemoteCategory(array $remoteCategories, $articleId)
    {
        $productToCategories = $this->productToRemoteCategoryRepository->getRemoteCategoryIds($articleId);
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
     * @param int $articleId
     */
    private function removeProductsFromNotAssignedRemoteCategories(array $assignedCategories, $articleId)
    {
        $currentProductCategoryIds = $this->productToRemoteCategoryRepository->getRemoteCategoryIds($articleId);

        $assignedCategoryIds = array_map(function (RemoteCategory $assignedCategory) {
            $assignedCategory->getId();
        }, $assignedCategories);

        /** @var int $currentProductCategoryId */
        foreach ($currentProductCategoryIds as $currentProductCategoryId) {
            if (!in_array($currentProductCategoryId, $assignedCategoryIds)) {
                $this->deleteAssignmentOfLocalCategories($currentProductCategoryId, $articleId);
                $this->productToRemoteCategoryRepository->deleteByConnectCategoryId($currentProductCategoryId, $articleId);
            }
        }
    }

    /**
     * @param int $currentProductCategoryId
     * @param int $articleId
     */
    private function deleteAssignmentOfLocalCategories($currentProductCategoryId, $articleId)
    {
        $localCategoriesIds = $this->manager->getConnection()->executeQuery(
            'SELECT local_category_id FROM s_plugin_connect_categories_to_local_categories WHERE remote_category_id = ?',
            [$currentProductCategoryId]
        )->fetchAll(\PDO::FETCH_COLUMN);
        if ($localCategoriesIds) {
            foreach ($localCategoriesIds as $categoryId) {
                $this->manager->getConnection()->executeQuery(
                    'DELETE FROM `s_articles_categories` WHERE `articleID` = ? AND `categoryID` = ?',
                    [$articleId, $categoryId]
                );
                $this->categoryDenormalization->removeAssignment($articleId, $categoryId);
            }
            $this->deleteEmptyConnectCategories($localCategoriesIds);
        }
    }

    /**
     * @param int[] $categoryIds
     */
    public function deleteEmptyConnectCategories($categoryIds)
    {
        foreach ($categoryIds as $categoryId) {
            $articleCount = (int) $this->manager->getConnection()->fetchColumn(
                'SELECT COUNT(id) FROM s_articles_categories WHERE categoryID = ?',
                [$categoryId]
            );
            if ($articleCount === 0) {
                $this->deleteEmptyCategory($categoryId);
            }
        }
    }

    /**
     * @param int $categoryId
     */
    private function deleteEmptyCategory($categoryId)
    {
        $connectImported = $this->manager->getConnection()->fetchColumn(
            'SELECT connect_imported_category FROM s_categories_attributes WHERE categoryID = ?',
            [$categoryId]
        );

        $childCount = (int) $this->manager->getConnection()->fetchColumn(
            'SELECT COUNT(id) FROM s_categories WHERE parent = ?',
            [$categoryId]
        );

        if ($connectImported == 1 && $childCount === 0) {
            $parent = (int) $this->manager->getConnection()->fetchColumn(
                'SELECT parent FROM s_categories WHERE `id` = ?',
                [$categoryId]
            );

            $this->manager->getConnection()->executeQuery(
                'DELETE FROM `s_categories` WHERE `id` = ?',
                [$categoryId]
            );

            $this->deleteEmptyCategory($parent);
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
            $categoryId = $this->checkAndCreateLocalCategory($category['name'], $category['categoryId'], $parentId);

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
     * @param string $categoryName
     * @param string $categoryKey
     * @param int $parentId
     * @return int
     */
    private function checkAndCreateLocalCategory($categoryName, $categoryKey, $parentId)
    {
        $id = $this->manager->getConnection()->fetchColumn('SELECT `id` 
            FROM `s_categories`
            WHERE `parent` = :parentId AND `description` = :description',
            [':parentId' => $parentId, ':description' => $categoryName]);

        if (!$id) {
            return $this->createLocalCategory($categoryName, $categoryKey, $parentId);
        }

        return $id;
    }

    /**
     * @param string $categoryName
     * @param string $categoryKey
     * @param int $parentId
     * @return int
     */
    public function createLocalCategory($categoryName, $categoryKey, $parentId)
    {
        $path = $this->manager->getConnection()->fetchColumn('SELECT `path` 
            FROM `s_categories`
            WHERE `id` = ?',
            [$parentId]);
        $suffix = ($path) ? "$parentId|" : "|$parentId|";
        $path = $path . $suffix;
        $now = new \DateTime('now');
        $timestamp = $now->format('Y-m-d H:i:s');
        $this->manager->getConnection()->executeQuery('INSERT INTO `s_categories` (`description`, `parent`, `path`, `active`, `added`, `changed`) 
            VALUES (?, ?, ?, 1, ?, ?)',
            [$categoryName, $parentId, $path, $timestamp, $timestamp]);
        $localCategoryId = $this->manager->getConnection()->fetchColumn('SELECT LAST_INSERT_ID()');

        $this->manager->getConnection()->executeQuery('INSERT INTO `s_categories_attributes` (`categoryID`, `connect_imported_category`) 
            VALUES (?, 1)',
            [$localCategoryId]);

        $remoteCategoryId = $this->manager->getConnection()->fetchColumn('SELECT `id` 
            FROM `s_plugin_connect_categories`
            WHERE `category_key` = ?',
            [$categoryKey]);
        $this->manager->getConnection()->executeQuery('INSERT INTO `s_plugin_connect_categories_to_local_categories` (`remote_category_id`, `local_category_id`) 
            VALUES (?, ?)',
            [$remoteCategoryId, $localCategoryId]);

        return $localCategoryId;
    }
}
