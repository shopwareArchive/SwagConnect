<?php

namespace Shopware\Bepado\Components\CategoryQuery;

use Bepado\SDK\Struct\Product;
use Doctrine\ORM\QueryBuilder;

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
        $sql = 'SELECT ca.bepado_export_mapping FROM s_categories_attributes ca ' .
               'INNER JOIN s_articles_categories ac ON ca.categoryID = ac.categoryID ' .
               'WHERE ac.articleID = ?';
        $rows = Shopware()->Db()->fetchAll($sql, array($id));

        $categories = array_filter(
            array_map(
                function ($row) {
                    // Flatten the array
                    return $row['bepado_export_mapping'];
                },
                $rows
            ), function($category) {
                // Don't allow vendor categories for export
                return strpos($category, '/vendor/') !== 0 && $category != '/vendor';
            }
        );

        usort($categories, array($this->relevanceSorter, 'sortBepadoCategoriesByRelevance'));

        return array_pop($categories);

    }
}
