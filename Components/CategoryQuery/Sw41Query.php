<?php

namespace Shopware\Bepado\Components\CategoryQuery;

use Bepado\SDK\Struct\Product;
use Doctrine\ORM\QueryBuilder;
use Shopware\Models\Category\Category;

class Sw41Query extends SwQuery
{
    /**
     * Return local SW categories for a given product
     *
     * @param Product $product
     * @return \Shopware\Models\Category\Category[]
     */
    public function getCategoriesByProduct(Product $product)
    {
        $repository = $this->getCategoryRepository();
        $builder = $repository->createQueryBuilder('c');
        $builder->join('c.attribute', 'ca');
        $builder->select('c');
        $builder->andWhere('ca.bepadoImportMapping = :mapping');

        $builder->leftJoin('c.children', 'children');
        $builder->andWhere('children.id IS NULL');

        $query = $builder->getQuery();
        $query->setHydrationMode($query::HYDRATE_OBJECT);

        /** @var $categories CategoryModel[] */
        $categories = array();
        foreach($product->categories as $category) {
            $categories = array_merge(
                $query->setParameter('mapping', $category)->execute()
            );
        }
        return $categories;
    }

    /**
     * Return bepado category mapping for the given product id
     *
     * @param $id
     * @return array
     */
    public function getBepadoCategoryForProduct($id)
    {
        $builder = $this->manager->createQueryBuilder();
        $builder->select(array('categories'))
            ->from('Shopware\Models\Category\Category', 'categories', 'categories.id')
            ->andWhere(':articleId MEMBER OF categories.articles')
            ->setParameters(array('articleId' => $id));

        $result = $builder->getQuery()->getResult();
        if (empty($result)) {
            return array();
        }

        $categories = array();
        /** @var \Shopware\Models\Category\Category $category */
        foreach ($result as $category) {
            list($key, $name) = $this->extractCategory($category);
            $categories[$key] = $name;
            $parent = $category;
            while ($parent = $parent->getParent()) {
                if (!$parent->getParentId()) {
                    continue;
                }
                list($key, $name) = $this->extractCategory($parent);
                $categories[$key] = $name;
            }
        }

        return $categories;
    }

    /**
     * Returns category path and category name as array
     * @param Category $category
     * @return array
     */
    private function extractCategory(Category $category)
    {
        $path = $this->getCategoryRepository()->getPathById($category->getId(), 'name', ' > ');
        $key = $this->normalizeCategory($path);

        return array($key, $category->getName());
    }

    /**
     * Normalize category name
     *
     * @param string $name
     * @return string
     */
    private function normalizeCategoryName($name)
    {
        return preg_replace('(\P{L}+)u', '_', strtolower($name));
    }

    /**
     * Convert a clear text category name into normalized format.
     *
     * @param string $categoryName
     * @return string
     */
    private function normalizeCategory($categoryName)
    {
        $path = preg_split('(\\s+>\\s+)', trim($categoryName));
        return '/' . implode(
            '/',
            array_map(array($this, 'normalizeCategoryName'), $path)
        );
    }
}
