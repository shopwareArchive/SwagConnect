<?php

namespace Shopware\Bepado\Components\CategoryResolver;

use Shopware\Bepado\Components\CategoryResolver;
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

    public function __construct(ModelManager $manager, CategoryRepository $categoryRepository)
    {
        $this->manager = $manager;
        $this->categoryRepository = $categoryRepository;
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
    private function collectOnlyLeafCategories($tree, array $categoryNames)
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
     * @param null $parent
     */
    private function convertTreeToEntities(array $tree, $parent = null)
    {
        if (!$parent) {
            $parent = $this->categoryRepository->find(3);
        }

        foreach ($tree as $categoryKey => $category) {
            $categoryModel = $this->categoryRepository->findOneBy(array('name' => $category['name']));
            if (!$categoryModel) {
                $categoryModel = new Category();
                $categoryModel->setParent($parent);
                $categoryModel->setName($category['name']);

                $this->manager->persist($categoryModel);
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
} 