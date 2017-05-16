<?php

namespace ShopwarePlugins\Connect\Components\CategoryResolver;

use ShopwarePlugins\Connect\Components\CategoryResolver;
use Shopware\CustomModels\Connect\RemoteCategoryRepository;
use Shopware\Models\Category\Category;
use Shopware\Models\Category\Repository as CategoryRepository;
use Shopware\Components\Model\ModelManager;
use ShopwarePlugins\Connect\Components\Config;

class AutoCategoryResolver implements CategoryResolver
{
    /**
     * @var ModelManager
     */
    private  $manager;

    /**
     * @var CategoryRepository
     */
    private $categoryRepository;

    /**
     * @var \Shopware\CustomModels\Connect\RemoteCategoryRepository
     */
    private $remoteCategoryRepository;

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
     */
    public function __construct(
        ModelManager $manager,
        CategoryRepository $categoryRepository,
        RemoteCategoryRepository $remoteCategoryRepository,
        Config $config
    )
    {
        $this->manager = $manager;
        $this->categoryRepository = $categoryRepository;
        $this->remoteCategoryRepository = $remoteCategoryRepository;
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
        $leafCategories = [];
        foreach ($tree as $node) {
            $mainCategory = $this->categoryRepository->findOneBy([
                'name' => $node['name'],
                'parentId' => 1,
            ]);

            // collect only leaf categories from current tree
            $leafCategories = array_merge($leafCategories, $this->convertTreeToEntities($node['children'], $mainCategory));
        }

        // return only leaf categories. Do not fetch them from database by name as before.
        // it is possible to have more than one subcategory "Boots" - CON-4589
        return array_map(function($category) {
            return $category['model'];
        }, $leafCategories);
    }

    /**
     * Loop categories tree recursive and
     * create same structure with entities
     *
     * @param array $node
     * @param null Category $parent
     * @param array $leafCollection
     * @return array
     */
    public function convertTreeToEntities(array $node, Category $parent = null, $leafCollection = array())
    {
        if (!$parent) {
            //full load of category entity
            $parent = $this->config->getDefaultShopCategory();
        }

        foreach ($node as $category) {
            $categoryModel = $this->categoryRepository->findOneBy(array(
                'name' => $category['name'],
                'parentId' => $parent->getId()
            ));

            if (!$categoryModel) {
                $categoryModel = $this->convertNodeToEntity($category, $parent);
            }

            if (!empty($category['children'])) {
                $leafCollection = $this->convertTreeToEntities($category['children'], $categoryModel, $leafCollection);
            } else {
                $leafCollection[] = array(
                    'model' => $categoryModel,
                    'categoryKey' => $category['categoryId'],
                );
            }
        }

        return $leafCollection;
    }

    /**
     * @param array $category
     * @param Category $parent
     * @return Category
     */
    public function convertNodeToEntity(array $category, Category $parent)
    {
        $categoryModel = new Category();
        $categoryModel->fromArray($this->getCategoryData($category['name']));
        $categoryModel->setParent($parent);

        $this->manager->persist($categoryModel);

        $categoryAttribute = $categoryModel->getAttribute();
        $categoryAttribute->setConnectImportedCategory(true);
        $this->manager->persist($categoryAttribute);

        /** @var \Shopware\CustomModels\Connect\RemoteCategory $remoteCategory */
        $remoteCategory = $this->remoteCategoryRepository->findOneBy(array('categoryKey' => $category['categoryId']));
        if ($remoteCategory) {
            $remoteCategory->setLocalCategory($categoryModel);
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
        $tree = array();
        uksort($categories, function($a, $b) {
            return strlen($a) - strlen($b);
        });

        if (strlen($idPrefix) > 0) {
            // find child categories by given prefix
            $childCategories = array_filter(array_keys($categories), function ($key) use ($idPrefix, $categories) {
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
            $tree[$key] = array(
                'name' => $categoryName,
                'children' => $children,
                'categoryId' => $key,
                'leaf' => empty($children),
            );
        }
        return $tree;
    }

    public function storeRemoteCategories(array $categories, $articleId)
    {
        // Shops connected to SEM projects don't need to store Shopware Connect categories
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
        return array (
            'name' => $name,
            'active' => true,
            'childrenCount' => 0,
            'text' => $name,
            'attribute' =>
                array (
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
                    'categoryId' => NULL,
                    'attribute1' => NULL,
                    'attribute2' => NULL,
                    'attribute3' => NULL,
                    'attribute4' => NULL,
                    'attribute5' => NULL,
                    'attribute6' => NULL,
                ),
        );
    }
} 