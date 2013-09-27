<?php

namespace Shopware\Bepado\CategoryQuery;

use Bepado\SDK\Struct\Product;
use Doctrine\ORM\QueryBuilder;

class Sw41Query extends SwQuery
{
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

        $builder->leftJoin('c.children', 'children');
        $builder->andWhere('children.id IS NULL');

        $query = $builder->getQuery();
        $query->setHydrationMode($query::HYDRATE_OBJECT);

        /** @var $categories CategoryModel[] */
        $categories = array();
        foreach($product->categories as $category) {
            $categories = array_merge(
                $query->execute(array('mapping' => $category))
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
        $sql = 'SELECT ca.bepado_mapping FROM s_categories_attributes ca ' .
               'INNER JOIN s_articles_categories ac ON ca.categoryID = ac.categoryID ' .
               'WHERE ac.articleID = ?';
        $rows = Shopware()->Db()->fetchAll($sql, array($id));

        return array_filter(array_map(function ($row) {
            return $row['bepado_mapping'];
        }, $rows));
    }
}
