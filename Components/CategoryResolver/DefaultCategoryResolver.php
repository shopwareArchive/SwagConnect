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
            if ($remoteCategory->hasLocalCategories()) {
                $localCategories = array_merge($localCategories, $remoteCategory->getLocalCategories());
            }
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

    /**
     * {@inheritdoc}
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

        $this->addProductToRemoteCategory($remoteCategories, $articleId);
        $this->removeProductsFromRemoteCategory($remoteCategories, $articleId);

        $this->manager->flush();
    }

    /**
     * @param RemoteCategory[] $remoteCategories
     * @param $articleId
     */
    private function addProductToRemoteCategory($remoteCategories, $articleId)
    {
        /** @var $remoteCategory \Shopware\CustomModels\Connect\RemoteCategory */
        foreach ($remoteCategories as $remoteCategory) {
            $productToCategory = $this->productToRemoteCategoryRepository->findOneBy([
                'articleId' => $articleId,
                'connectCategoryId' => $remoteCategory->getId(),
            ]);
            if ($productToCategory) {
                continue;
            }

            $productToCategory = new ProductToRemoteCategory();
            $productToCategory->setArticleId($articleId);
            $productToCategory->setConnectCategory($remoteCategory);
            $this->manager->persist($productToCategory);
        }
    }

    /**
     * @param \Shopware\CustomModels\Connect\RemoteCategory[] $assignedCategories
     * @param $articleId
     */
    private function removeProductsFromRemoteCategory(array $assignedCategories, $articleId)
    {
        $currentProductCategories = $this->productToRemoteCategoryRepository->getArticleRemoteCategories($articleId);

        $assignedCategoryIds = array_map(function (RemoteCategory $assignedCategory) {
            $assignedCategory->getId();
        }, $assignedCategories);

        /** @var ProductToRemoteCategory $currentProductCategory */
        foreach ($currentProductCategories as $currentProductCategory) {
            if (!in_array($currentProductCategory->getConnectCategoryId(), $assignedCategoryIds)) {
                //DELETE PRODUCT ASSIGNMENT
                $this->manager->remove($currentProductCategory);
            }
        }
    }
}
