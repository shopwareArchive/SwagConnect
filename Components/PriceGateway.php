<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Components;

use Shopware\Models\Customer\Group;

/**
 * Price gateway
 *
 * @category  Shopware
 *
 * @copyright Copyright (c) shopware AG (http://www.shopware.de)
 */
class PriceGateway
{
    private $db;

    public function __construct(\Enlight_Components_Db_Adapter_Pdo_Mysql $db)
    {
        $this->db = $db;
    }

    /**
     * Returns count of product without
     * configured price
     *
     * @param Group $group
     * @param $priceField
     *
     * @throws \Zend_Db_Statement_Exception
     *
     * @return array
     */
    public function countProductsWithoutConfiguredPrice(Group $group = null, $priceField)
    {
        if ($priceField == 'detailPurchasePrice') {
            $query = $this->db->query('
                SELECT COUNT(sad.id)
                FROM s_articles_details sad
                LEFT JOIN s_plugin_connect_items spci ON sad.id = spci.article_detail_id
                WHERE spci.shop_id IS NULL AND sad.purchaseprice = 0
            ');
        } else {
            $query = $this->db->query("
                SELECT COUNT(sad.id)
                FROM s_articles_details sad
                LEFT JOIN s_articles_prices sap ON sad.id = sap.articledetailsID AND sap.pricegroup = ?
                LEFT JOIN s_plugin_connect_items spci ON sad.id = spci.article_detail_id
                WHERE spci.shop_id IS NULL AND sap.{$priceField} IS NULL OR sap.{$priceField} = 0
            ", [$group->getKey()]);
        }

        return (int) $query->fetchColumn();
    }

    /**
     * Returns count of product with
     * configured price
     *
     * @param Group $group
     * @param $priceField
     *
     * @throws \Zend_Db_Statement_Exception
     *
     * @return array
     */
    public function countProductsWithConfiguredPrice(Group $group = null, $priceField)
    {
        if ($priceField == 'detailPurchasePrice') {
            $query = $this->db->query('
                SELECT COUNT(sad.id)
                FROM s_articles_details sad
                LEFT JOIN s_plugin_connect_items spci ON sad.id = spci.article_detail_id
                WHERE spci.shop_id IS NULL AND sad.purchaseprice > 0
            ');
        } else {
            $query = $this->db->query("
                SELECT COUNT(sad.id)
                FROM s_articles_details sad
                LEFT JOIN s_articles_prices sap ON sad.id = sap.articledetailsID AND sap.pricegroup = ?
                LEFT JOIN s_plugin_connect_items spci ON sad.id = spci.article_detail_id
                WHERE spci.shop_id IS NULL AND sap.{$priceField} IS NOT NULL AND sap.{$priceField} > 0
            ", [$group->getKey()]);
        }

        return (int) $query->fetchColumn();
    }

    /**
     * Returns count of product including variants for a group
     *
     * @param Group $group
     *
     * @throws \Zend_Db_Statement_Exception
     *
     * @return array
     */
    public function countProducts(Group $group = null)
    {
        $query = $this->db->query('
            SELECT COUNT(sad.id)
            FROM s_articles_details sad
            LEFT JOIN s_articles_prices sap ON sad.id = sap.articledetailsID AND sap.pricegroup = ?
            LEFT JOIN s_plugin_connect_items spci ON sad.id = spci.article_detail_id
            WHERE spci.shop_id IS NULL', [$group->getKey()]);

        return (int) $query->fetchColumn();
    }
}
