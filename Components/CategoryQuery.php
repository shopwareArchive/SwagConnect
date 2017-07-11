<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Components;

interface CategoryQuery
{
    /**
     * @param $id
     * @return array
     */
    public function getConnectCategoryForProduct($id);

    /**
     * @return CategoryQuery\RelevanceSorter
     */
    public function getRelevanceSorter();
}
