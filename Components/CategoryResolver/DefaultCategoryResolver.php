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
    public function resolve(array $categories, $shopId)
    {
        $localCategories = [];
        /** @var \Shopware\CustomModels\Connect\RemoteCategory[] $remoteCategoriesModels */
        $remoteCategoriesModels = $this->remoteCategoryRepository->findBy(['categoryKey' => array_keys($categories), 'shopId' => $shopId]);

        foreach ($remoteCategoriesModels as $remoteCategory) {
            if ($remoteCategory->hasLocalCategories()) {
                $localCategories = array_merge($localCategories, $remoteCategory->getLocalCategories());
            }
        }

        return array_map(function ($category) {
            return $category->getId();
        }, $localCategories);
    }

    /**
     * {@inheritdoc}
     */
    public function generateTree(array $categories, $idPrefix = '')
    {
        return [];
    }
}
