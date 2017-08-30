<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Components\CategoryResolver;

use ShopwarePlugins\Connect\Components\CategoryResolver;
use Shopware\CustomModels\Connect\ProductToRemoteCategory;
use Shopware\CustomModels\Connect\ProductToRemoteCategoryRepository;
use Shopware\CustomModels\Connect\RemoteCategory;
use Shopware\CustomModels\Connect\RemoteCategoryRepository;
use Shopware\Components\Model\ModelManager;

class DefaultCategoryResolver implements CategoryResolver
{
    /**
     * @var ModelManager
     */
    private $manager;

    /**
     * @var \Shopware\CustomModels\Connect\RemoteCategoryRepository
     */
    private $remoteCategoryRepository;

    /**
     * @var \Shopware\CustomModels\Connect\ProductToRemoteCategoryRepository
     */
    private $productToRemoteCategoryRepository;

    public function __construct(
        ModelManager $manager,
        RemoteCategoryRepository $remoteCategoryRepository,
        ProductToRemoteCategoryRepository $productToRemoteCategoryRepository
    ) {
        $this->manager = $manager;
        $this->remoteCategoryRepository = $remoteCategoryRepository;
        $this->productToRemoteCategoryRepository = $productToRemoteCategoryRepository;
    }

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

        $this->manager->flush();
    }
}
