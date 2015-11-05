<?php

namespace Shopware\Connect\Components;

use Bepado\SDK\Struct\Product;

interface CategoryQuery
{
    /**
     * @param $id
     * @return array
     */
    public function getConnectCategoryForProduct($id);

    /**
     * @param Product $product
     * @return \Shopware\Models\Category\Category[]
     */
    public function getCategoriesByProduct(Product $product);

    /**
     * @return CategoryQuery\RelevanceSorter
     */
    public function getRelevanceSorter();
}
