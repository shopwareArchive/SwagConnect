<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Components;

interface CategoryResolver
{
    /**
     * Returns array with category entities
     * if they don't exist will be created
     *
     * @param array $categories
     *
     * @return \Shopware\Models\Category\Category[]
     */
    public function resolve(array $categories);

    /**
     * Generates categories tree by given array of categories
     *
     * @param array  $categories
     * @param string $idPrefix
     *
     * @return array
     */
    public function generateTree(array $categories, $idPrefix = '');

    /**
     * Stores raw Shopware Connect categories
     *
     * @param array $categories
     * @param $articleId
     */
    public function storeRemoteCategories(array $categories, $articleId);
}
