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
