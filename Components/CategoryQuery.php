<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Components;

use Shopware\Connect\Struct\Product;

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
