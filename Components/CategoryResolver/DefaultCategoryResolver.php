<?php

namespace Shopware\Bepado\Components\CategoryResolver;

use Shopware\Bepado\Components\CategoryResolver;
use Shopware\CustomModels\Bepado\ProductToRemoteCategory;
use Shopware\CustomModels\Bepado\ProductToRemoteCategoryRepository;
use Shopware\CustomModels\Bepado\RemoteCategory;
use Shopware\CustomModels\Bepado\RemoteCategoryRepository;
use Shopware\Components\Model\ModelManager;

class DefaultCategoryResolver implements CategoryResolver
{
    /**
     * @var ModelManager
     */
    private  $manager;

    /**
     * @var \Shopware\CustomModels\Bepado\RemoteCategoryRepository
     */
    private  $remoteCategoryRepository;

    /**
     * @var \Shopware\CustomModels\Bepado\ProductToRemoteCategoryRepository
     */
    private $productToRemoteCategoryRepository;

    public function __construct(
        ModelManager $manager,
        RemoteCategoryRepository $remoteCategoryRepository,
        ProductToRemoteCategoryRepository $productToRemoteCategoryRepository
    )
    {
        $this->manager = $manager;
        $this->remoteCategoryRepository = $remoteCategoryRepository;
        $this->productToRemoteCategoryRepository = $productToRemoteCategoryRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(array $categories)
    {
        $localCategories = array();
        /** @var \Shopware\CustomModels\Bepado\RemoteCategory[] $remoteCategoriesModels */
        $remoteCategoriesModels = $this->remoteCategoryRepository->findBy(array('categoryKey' => array_keys($categories)));

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
        return array();
    }

    /**
     * {@inheritdoc}
     */
    public function storeRemoteCategories(array $categories, $articleId)
    {
        $remoteCategories = array();
        foreach ($categories as $categoryKey => $category) {
            $remoteCategory = $this->remoteCategoryRepository->findOneBy(array('categoryKey' => $categoryKey));
            if (!$remoteCategory) {
                $remoteCategory = new RemoteCategory();
                $remoteCategory->setCategoryKey($categoryKey);
            }
            $remoteCategory->setLabel($category);
            $this->manager->persist($remoteCategory);
            $remoteCategories[] = $remoteCategory;
        }
        $this->manager->flush();

        /** @var  $remoteCategory \Shopware\CustomModels\Bepado\RemoteCategory */
        foreach ($remoteCategories as $remoteCategory) {
            $productToCategory = $this->productToRemoteCategoryRepository->findOneBy(array(
                'articleId' => $articleId,
                'connectCategoryId' => $remoteCategory->getId(),
            ));
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