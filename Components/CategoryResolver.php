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

//ToDo Refactor this one
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
     * @param int $shopId
     * @param string $stream
     * @return \Shopware\Models\Category\Category[]
     */
    abstract public function resolve(array $categories, $shopId, $stream);

    /**
     * Generates categories tree by given array of categories
     *
     * @param array $categories
     * @param string $idPrefix
     * @return array
     */
    public function generateTree(array $categories, $idPrefix = '')
    {
        $tree = [];

        if (strlen($idPrefix) > 0) {
            $filteredCategories = $this->findChildCategories($categories, $idPrefix);
        } else {
            $filteredCategories = $this->filterMainCategories($categories);
        }

        foreach ($filteredCategories as $key => $categoryName) {
            $children = $this->generateTree($categories, $key);
            $tree[$key] = [
                'name' => $categoryName,
                'children' => $children,
                'categoryId' => $key,
                'leaf' => empty($children),
            ];
        }

        return $tree;
    }

    /**
     * Stores raw Shopware Connect categories
     *
     * @param array $categories
     * @param int $articleId
     * @param int $shopId
     * @return void
     */
    public function storeRemoteCategories(array $categories, $articleId, $shopId)
    {
        $remoteCategories = [];
        foreach ($categories as $categoryKey => $category) {
            $remoteCategory = $this->remoteCategoryRepository->findOneBy(['categoryKey' => $categoryKey, 'shopId' => $shopId]);
            if (!$remoteCategory) {
                $remoteCategory = new RemoteCategory();
                $remoteCategory->setCategoryKey($categoryKey);
                $remoteCategory->setShopId($shopId);
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
            return $assignedCategory->getId();
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
            $this->manager->getConnection()->executeQuery(
                'DELETE FROM `s_articles_categories` WHERE `articleID` = ? AND `categoryID` IN (?)',
                [$articleId, $localCategoriesIds],
                [\PDO::PARAM_INT, \Doctrine\DBAL\Connection::PARAM_STR_ARRAY]
            );
            foreach ($localCategoriesIds as $categoryId) {
                $this->categoryDenormalization->removeAssignment($articleId, $categoryId);
            }

            $this->deleteEmptyConnectCategories($localCategoriesIds);
        }
    }

    /**
     * @param int[] $categoryIds
     */
    public function deleteEmptyConnectCategories(array $categoryIds)
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
        if ($connectImported == 1 && $this->countChildCategories($categoryId) === 0) {
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
     * @param int $shopId
     * @param string $stream
     * @param bool $returnOnlyLeafs
     * @param array $categories
     * @return array
     */
    public function convertTreeToKeys(array $node, $parentId, $shopId, $stream, $returnOnlyLeafs = true, $categories = [])
    {
        foreach ($node as $category) {
            $categoryId = $this->checkAndCreateLocalCategory($category['name'], $category['categoryId'], $parentId, $shopId, $stream);

            if ((!$returnOnlyLeafs) || (empty($category['children']))) {
                $categories[] = [
                    'categoryKey' => $categoryId,
                    'parentId' => $parentId,
                    'remoteCategory' => $category['categoryId']
                ];
            }

            if (!empty($category['children'])) {
                $categories = $this->convertTreeToKeys($category['children'], $categoryId, $shopId, $stream, $returnOnlyLeafs, $categories);
            }
        }

        return $categories;
    }

    /**
     * @param string $categoryName
     * @param string $categoryKey
     * @param int $parentId
     * @param int $shopId
     * @param string stream
     * @return int
     */
    private function checkAndCreateLocalCategory($categoryName, $categoryKey, $parentId, $shopId, $stream)
    {
        $id = $this->manager->getConnection()->fetchColumn('SELECT `s_categories`.`id` 
            FROM `s_categories`
            JOIN `s_categories_attributes` ON s_categories.id = s_categories_attributes.categoryID
            WHERE s_categories.`parent` = :parentId AND s_categories.`description` = :description AND s_categories_attributes.connect_imported_category = 1',
            [':parentId' => $parentId, ':description' => $categoryName]);

        if (!$id) {
            return $this->createLocalCategory($categoryName, $categoryKey, $parentId, $shopId, $stream);
        }

        $remoteCategoryId = $this->manager->getConnection()->fetchColumn('SELECT ctlc.remote_category_id
             FROM s_plugin_connect_categories_to_local_categories AS ctlc
             JOIN s_plugin_connect_categories AS cc ON cc.id = ctlc.remote_category_id
             WHERE cc.category_key = ? AND cc.shop_id = ? AND ctlc.stream = ?',
            [$categoryKey, $shopId, $stream]);

        //create entry in connect_categories_to_local_categories for the given stream -> for "merging" when assigning an other stream to the same category
        if (!$remoteCategoryId) {
            $this->manager->getConnection()->executeQuery('INSERT INTO s_plugin_connect_categories_to_local_categories (remote_category_id, local_category_id, stream)
                VALUES (
                    (SELECT id FROM s_plugin_connect_categories WHERE category_key = ? AND shop_id = ?),
                    ?,
                    ?
                )',
                [$categoryKey, $shopId, $id, $stream]);
        }

        return $id;
    }

    /**
     * @param string $categoryName
     * @param string $categoryKey
     * @param int $parentId
     * @param int $shopId
     * @param string $stream
     * @return int
     */
    public function createLocalCategory($categoryName, $categoryKey, $parentId, $shopId, $stream)
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
            WHERE `category_key` = ? AND `shop_id` = ?',
            [$categoryKey, $shopId]);
        $this->manager->getConnection()->executeQuery('INSERT INTO `s_plugin_connect_categories_to_local_categories` (`remote_category_id`, `local_category_id`, `stream`) 
            VALUES (?, ?, ?)',
            [$remoteCategoryId, $localCategoryId, $stream]);

        return $localCategoryId;
    }

    /**
     * @param $categoryId
     * @return int
     */
    private function countChildCategories($categoryId)
    {
        return (int) $this->manager->getConnection()->fetchColumn(
            'SELECT COUNT(id) FROM s_categories WHERE parent = ?',
            [$categoryId]
        );
    }

    /**
     * @param array $categories
     * @param $idPrefix
     * @return array
     */
    private function findChildCategories(array $categories, $idPrefix)
    {
        $childCategories = array_filter(array_keys($categories), function ($key) use ($idPrefix) {
            return strpos($key, $idPrefix) === 0 && strrpos($key, '/') === strlen($idPrefix);
        });
        $filteredCategories = array_intersect_key($categories, array_flip($childCategories));

        return $filteredCategories;
    }

    /**
     * @param array $categories
     * @return array
     */
    private function filterMainCategories(array $categories)
    {
        $matchedKeys = array_filter(array_keys($categories), function ($key) {
            return strrpos($key, '/') === 0;
        });
        $filteredCategories = array_intersect_key($categories, array_flip($matchedKeys));

        return $filteredCategories;
    }
}
