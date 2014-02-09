<?php

namespace Shopware\Bepado\Components\CategoryQuery;

use Bepado\SDK\Struct\Product;
use Doctrine\ORM\QueryBuilder;

class Sw40Query extends SwQuery
{
    private $productCategoryQuery;

    /**
     * @param Product $product
     * @return \Shopware\Models\Category\Category[]
     */
    public function getCategoriesByProduct(Product $product)
    {
        $repository = $this->getCategoryRepository();
        $builder = $repository->createQueryBuilder('c');
        $builder->join('c.attribute', 'ca');
        $builder->select('c');
        $builder->andWhere('ca.bepadoMapping = :mapping');
        $builder->andWhere('c.right-c.left = 1');
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
     * @param $id
     * @return array
     */
    public function getRowProductCategoriesById($id)
    {
        if($this->productCategoryQuery === null) {
            $this->productCategoryQuery = $this->getProductCategoriesQuery();
        }
        $result = $this->productCategoryQuery->setParameter('id', $id)->execute();
        $data = array();
        foreach($result as $row) {
            if($row['mapping'] !== null) {
                $data[] = $row['mapping'];
            }
        }
        return $data;
    }

    /**
     * @return Query
     */
    private function getSubProductCategoriesQuery()
    {
        $repository = $this->getCategoryRepository();
        $builder = $repository->createQueryBuilder('s');
        $builder->join('s.attribute', 'st');
        $builder->select('MAX(st.bepadoMapping) as subMapping');
        $builder->andWhere('c.left > s.left')
            ->andWhere('c.right < s.right');
        $builder->orderBy('s.right', 'DESC');
        $query = $builder->getQuery();
        $query->setHydrationMode($query::HYDRATE_SCALAR);
        return $query;
    }

    /**
     * @return Query
     */
    private function getProductCategoriesQuery()
    {
        $repository = $this->getArticleRepository();
        $builder = $repository->createQueryBuilder('a');
        $builder->join('a.categories', 'c');
        $builder->join('c.attribute', 'ct');

        $subQuery = $this->getSubProductCategoriesQuery()->getDQL();

        $builder->select('IFNULL(ct.bepadoMapping, (' . $subQuery . ')) as mapping')
            ->where('a.id = :id');
        $builder->groupBy('mapping');

        return $builder->getQuery();
    }
}
