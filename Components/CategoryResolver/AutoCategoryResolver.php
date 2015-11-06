<?php

namespace ShopwarePlugins\Connect\Components\CategoryResolver;

use ShopwarePlugins\Connect\Components\CategoryResolver;
use Shopware\CustomModels\Connect\RemoteCategoryRepository;
use Shopware\Models\Category\Category;
use Shopware\Models\Category\Repository as CategoryRepository;
use Shopware\Components\Model\ModelManager;

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

    public function __construct(
        ModelManager $manager,
        CategoryRepository $categoryRepository,
        RemoteCategoryRepository $remoteCategoryRepository
    )
    {
        $this->manager = $manager;
        $this->categoryRepository = $categoryRepository;
        $this->remoteCategoryRepository = $remoteCategoryRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(array $categories)
    {
        $tree = $this->generateTree($categories);

        $this->convertTreeToEntities($tree);
        $categoryNames = array();
        $categoryNames = $this->collectOnlyLeafCategories($tree, $categoryNames);

        return $this->categoryRepository->findBy(array(
            'name' => $categoryNames
        ));
    }

    /**
     * Collect only categories without children
     *
     * @param array $tree
     * @param array $categoryNames
     * @return array
     */
    public function collectOnlyLeafCategories($tree, array $categoryNames)
    {
        foreach ($tree as $category) {
            if (empty($category['children'])) {
                $categoryNames[] = $category['name'];
            } else {
                $categoryNames = $this->collectOnlyLeafCategories($category['children'], $categoryNames);
            }
        }

        return $categoryNames;
    }

    /**
     * Loop categories tree recursive and
     * create same structure with entities
     *
     * @param array $tree
     * @param \Shopware\Models\Category\Category|null $parent
     */
    public function convertTreeToEntities(array $tree, $parent = null)
    {
        if (!$parent) {
            $parent = $this->categoryRepository->find(3);
        }

        foreach ($tree as $category) {
            $categoryModel = $this->categoryRepository->findOneBy(array('name' => $category['name']));
            if (!$categoryModel) {
                $categoryModel = new Category();
                $categoryModel->fromArray($this->getCategoryData($category['name']));
                $categoryModel->setParent($parent);

                $this->manager->persist($categoryModel);

                $categoryAttribute = $categoryModel->getAttribute();
                $categoryAttribute->setConnectImportedCategory(true);
                $this->manager->persist($categoryAttribute);

                /** @var \Shopware\CustomModels\Connect\RemoteCategory $remoteCategory */
                $remoteCategory = $this->remoteCategoryRepository->findOneBy(array('categoryKey' => $category['id']));
                if ($remoteCategory) {
                    $remoteCategory->setLocalCategory($categoryModel);
                    $this->manager->persist($remoteCategory);
                }

                $this->manager->flush();
            }

            if (!empty($category['children'])) {
                $this->convertTreeToEntities($category['children'], $categoryModel);
            }
        }
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
            $localPrefix = $key;
            $name = $categoryName;
            $tree[$localPrefix] = array(
                'name' => $name,
                'children' => $this->generateTree($categories, $localPrefix),
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