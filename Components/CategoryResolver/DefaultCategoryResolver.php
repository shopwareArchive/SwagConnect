<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Components\CategoryResolver;

use ShopwarePlugins\Connect\Components\CategoryResolver;

class DefaultCategoryResolver extends CategoryResolver
{
    /**
     * {@inheritdoc}
     */
    public function resolve(array $categories, $shopId, $stream)
    {
        $tree = $this->generateTree($categories);

        return array_keys($this->findAssignedLocalCategories($tree, $shopId, $stream));
    }

    /**
     * @param array $children
     * @param int $shopId
     * @param string $stream
     * @return array
     */
    private function findAssignedLocalCategories(array $children, $shopId, $stream)
    {
        $mappedCategories = [];
        foreach ($children as $category) {
            $localCategories = $this->getDirectlyAssignedCategories($category['categoryId'], $shopId, $stream);
            //use + not array_merge because we want to preserve the numeric keys
            $mappedCategories = $mappedCategories + $this->buildLocalSubtrees($shopId, $stream, $localCategories, $category['children']);
            $mappedCategories = $mappedCategories + $this->findAssignedLocalCategoriesForChildCategories($shopId, $stream, $category['children']);
        }

        return $mappedCategories;
    }

    /**
     * @param int $remoteCategoryId
     * @param int $shopId
     * @param string $stream
     * @return array
     */
    private function getDirectlyAssignedCategories($remoteCategoryId, $shopId, $stream)
    {
        return (array) $this->manager->getConnection()->executeQuery('
              SELECT pclc.local_category_id
              FROM s_plugin_connect_categories_to_local_categories AS pclc
              INNER JOIN s_plugin_connect_categories AS pcc ON pcc.id = pclc.remote_category_id
              WHERE pcc.category_key = ? AND pcc.shop_id = ? AND (pclc.stream = ? OR pclc.stream IS NULL) ',
            [$remoteCategoryId, $shopId, $stream]
        )->fetchAll(\PDO::FETCH_COLUMN);
    }

    /**
     * @param int $shopId
     * @param string $stream
     * @param array $localCategories
     * @param array $children
     * @return array
     */
    private function buildLocalSubtrees($shopId, $stream, array $localCategories, array $children)
    {
        $mappedCategories = [];
        foreach ($localCategories as $localCategory) {
            $mappedCategories[$localCategory] = true;
            if (count($children) > 0) {
                foreach ($this->convertTreeToKeys($children, $localCategory, $shopId, $stream) as $local) {
                    $mappedCategories[$local['categoryKey']] = true;
                }
            }
        }

        return $mappedCategories;
    }

    /**
     * @param int $shopId
     * @param string $stream
     * @param array $children
     * @return array
     */
    private function findAssignedLocalCategoriesForChildCategories($shopId, $stream, $children)
    {
        $childCategories = [];
        if (count($children) > 0) {
            $childCategories = $this->findAssignedLocalCategories($children, $shopId, $stream);
        }

        return $childCategories;
    }
}
