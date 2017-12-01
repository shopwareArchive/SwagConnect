<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Components\CategoryResolver;

use ShopwarePlugins\Connect\Components\CategoryResolver;

class DefaultCategoryResolver extends CategoryResolver
{
    /**
     * {@inheritdoc}
     */
    public function resolve(array $categories, $shopId, $stream)
    {
        $remoteCategoriesIds = $this->manager->getConnection()->executeQuery('
            SELECT id
            FROM s_plugin_connect_categories
            WHERE shop_id = ? AND category_key IN (?)',
            [$shopId, array_keys($categories)],
            [\PDO::PARAM_INT, \Doctrine\DBAL\Connection::PARAM_STR_ARRAY])->fetchAll(\PDO::FETCH_COLUMN);

        return $this->manager->getConnection()->executeQuery('
            SELECT local_category_id
            FROM s_plugin_connect_categories_to_local_categories
            WHERE remote_category_id IN (?) AND (stream = ? OR stream IS NULL) ',
            [$remoteCategoriesIds, $stream],
            [\Doctrine\DBAL\Connection::PARAM_INT_ARRAY, \PDO::PARAM_STR])->fetchAll(\PDO::FETCH_COLUMN);
    }

    /**
     * {@inheritdoc}
     */
    public function generateTree(array $categories, $idPrefix = '')
    {
        return [];
    }
}
