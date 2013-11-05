<?php

namespace Shopware\Bepado;

use Bepado\SDK\Struct\Product;

interface CategoryQuery
{
    /**
     * @param $id
     * @return array
     */
    public function getRowProductCategoriesById($id);

    /**
     * @param Product $product
     * @return \Shopware\Models\Category\Category[]
     */
    public function getCategoriesByProduct(Product $product);
}
