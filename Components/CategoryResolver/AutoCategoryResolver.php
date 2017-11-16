<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Components\CategoryResolver;

use ShopwarePlugins\Connect\Components\CategoryResolver;
use Shopware\CustomModels\Connect\RemoteCategoryRepository;
use Shopware\Models\Category\Category;
use Shopware\Models\Category\Repository as CategoryRepository;
use Shopware\Components\Model\ModelManager;
use ShopwarePlugins\Connect\Components\Config;
use Shopware\CustomModels\Connect\ProductToRemoteCategoryRepository;
use Shopware\CustomModels\Connect\ProductToRemoteCategory;
use Shopware\Components\Model\CategoryDenormalization;

class AutoCategoryResolver extends CategoryResolver
{
    /**
     * @var Config
     */
    private $config;

    /**
     * AutoCategoryResolver constructor.
     * @param ModelManager $manager
     * @param CategoryRepository $categoryRepository
     * @param RemoteCategoryRepository $remoteCategoryRepository
     * @param Config $config
     * @param ProductToRemoteCategoryRepository $productToRemoteCategoryRepository
     */
    public function __construct(
        ModelManager $manager,
        CategoryRepository $categoryRepository,
        RemoteCategoryRepository $remoteCategoryRepository,
        Config $config,
        CategoryDenormalization $categoryDenormalization,
        ProductToRemoteCategoryRepository $productToRemoteCategoryRepository = null
    ) {
        if (!$productToRemoteCategoryRepository) {
            $productToRemoteCategoryRepository = $manager->getRepository(ProductToRemoteCategory::class);
        }
        parent::__construct(
            $manager,
            $remoteCategoryRepository,
            $productToRemoteCategoryRepository,
            $categoryRepository,
            $categoryDenormalization
        );

        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(array $categories, $shopId)
    {
        $tree = $this->generateTree($categories);

        // we need to foreach, cause we may have two main nodes
        // example:
        // Deutsch/Category/Subcategory
        // English/Category/Subcategory
        $remoteCategories = [];
        foreach ($tree as $node) {
            $mainCategory = $this->categoryRepository->findOneBy([
                'name' => $node['name'],
                'parentId' => 1,
            ]);
            // if connectTree has a Subtree starting with Spanish but MerchantShop has no mainCategory Spanish
            // the categories below Spanish won't be created
            if ($mainCategory == null) {
                continue;
            }

            $remoteCategories = array_merge($remoteCategories, $this->convertTreeToKeys($node['children'], $mainCategory->getId(), $shopId));
        }

        // Collect all, not only leaf categories. Some customers use them to assign products.
        // Do not fetch them from database by name as before.
        // it is possible to have more than one subcategory "Boots" - CON-4589
        return array_map(function ($category) {
            return $category['categoryKey'];
        }, $remoteCategories);
    }

    /**
     * {@inheritdoc}
     */
    public function generateTree(array $categories, $idPrefix = '')
    {
        $tree = [];

        if (strlen($idPrefix) > 0) {
            // find child categories by given prefix
            $childCategories = array_filter(array_keys($categories), function ($key) use ($idPrefix) {
                return strpos($key, $idPrefix) === 0 && strrpos($key, '/') === strlen($idPrefix);
            });
            $filteredCategories = array_intersect_key($categories, array_flip($childCategories));
        } else {
            // filter only main categories
            $matchedKeys = array_filter(array_keys($categories), function ($key) {
                return strrpos($key, '/') === 0;
            });
            $filteredCategories = array_intersect_key($categories, array_flip($matchedKeys));
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
}
