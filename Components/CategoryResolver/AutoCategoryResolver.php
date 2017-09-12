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

class AutoCategoryResolver extends CategoryResolver
{
    /**
     * @var CategoryRepository
     */
    private $categoryRepository;

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
        ProductToRemoteCategoryRepository $productToRemoteCategoryRepository = null
    ) {
        if (!$productToRemoteCategoryRepository) {
            $productToRemoteCategoryRepository = $manager->getRepository(ProductToRemoteCategory::class);
        }
        parent::__construct(
            $manager,
            $remoteCategoryRepository,
            $productToRemoteCategoryRepository
        );

        $this->categoryRepository = $categoryRepository;
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(array $categories)
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

            $remoteCategories = $this->convertTreeToKeys($node['children'], $mainCategory->getId());
        }

        // Collect all, not only leaf categories. Some customers use them to assign products.
        // Do not fetch them from database by name as before.
        // it is possible to have more than one subcategory "Boots" - CON-4589
        return array_map(function ($category) {
            return $category['categoryKey'];
        }, $remoteCategories);
    }

    /**
     * Loop through category tree and fetch ids
     *
     * @param array $node
     * @param string $parentId
     * @param bool $returnOnlyLeafs
     * @param array $categories
     * @return array
     */
    public function convertTreeToKeys(array $node, $parentId, $returnOnlyLeafs = true, $categories = [])
    {
        foreach ($node as $category) {
            $categoryId = $this->checkAndCreateLocalCategory($category, $parentId);

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
     * @param array $category
     * @param string $parentId
     * @return Category
     */
    public function convertNodeToEntity(array $category, $parentId)
    {
        $categoryModel = new Category();
        $categoryModel->fromArray($this->getCategoryData($category['name']));

        $parent = $this->categoryRepository->findOneBy([
            'id' => $parentId
        ]);
        $categoryModel->setParent($parent);

        $this->manager->persist($categoryModel);

        $categoryAttribute = $categoryModel->getAttribute();
        $categoryAttribute->setConnectImportedCategory(true);
        $this->manager->persist($categoryAttribute);

        /** @var \Shopware\CustomModels\Connect\RemoteCategory $remoteCategory */
        $remoteCategory = $this->remoteCategoryRepository->findOneBy(['categoryKey' => $category['categoryId']]);
        if ($remoteCategory) {
            $remoteCategory->addLocalCategory($categoryModel);
            $this->manager->persist($remoteCategory);
        }

        $this->manager->flush();

        return $categoryModel;
    }

    /**
     * {@inheritdoc}
     */
    public function generateTree(array $categories, $idPrefix = '')
    {
        $tree = [];
        uksort($categories, function ($a, $b) {
            return strlen($a) - strlen($b);
        });

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

    /**
     * Generate category data array
     * it's used to create category and
     * attribute from array
     *
     * @param string $name
     * @return array
     */
    private function getCategoryData($name)
    {
        return [
            'name' => $name,
            'active' => true,
            'childrenCount' => 0,
            'text' => $name,
            'attribute' => [
                    'id' => 0,
                    'parent' => 0,
                    'name' => 'Deutsch',
                    'position' => 0,
                    'active' => true,
                    'childrenCount' => 0,
                    'text' => '',
                    'cls' => '',
                    'leaf' => false,
                    'allowDrag' => false,
                    'parentId' => 0,
                    'categoryId' => null,
                    'attribute1' => null,
                    'attribute2' => null,
                    'attribute3' => null,
                    'attribute4' => null,
                    'attribute5' => null,
                    'attribute6' => null,
                ],
        ];
    }

    /**
     * @param array $category
     * @param string $parentId
     * @return Category
     */
    private function checkAndCreateLocalCategory($category, $parentId)
    {
        $categoryModel = $this->categoryRepository->findOneBy([
            'name' => $category['name'],
            'parentId' => $parentId
        ]);

        if (!$categoryModel) {
            $categoryModel = $this->convertNodeToEntity($category, $parentId);
        }

        return $categoryModel->getId();
    }
}
