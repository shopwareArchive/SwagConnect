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
    public function resolve(array $categories)
    {
        $localCategories = [];
        /** @var \Shopware\CustomModels\Connect\RemoteCategory[] $remoteCategoriesModels */
        $remoteCategoriesModels = $this->remoteCategoryRepository->findBy(['categoryKey' => array_keys($categories)]);

        foreach ($remoteCategoriesModels as $remoteCategory) {
            $localCategory = $remoteCategory->getLocalCategory();
            if (!$localCategory) {
                continue;
            }

            $localCategories[] = $localCategory;
        }

        return $localCategories;
    }

    /**
     * {@inheritdoc}
     */
    public function generateTree(array $categories, $idPrefix = '')
    {
        return [];
    }
}
