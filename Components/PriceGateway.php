<?php
/**
 * Shopware 5
 * Copyright (c) shopware AG
 *
 * According to our dual licensing model, this program can be used either
 * under the terms of the GNU Affero General Public License, version 3,
 * or under a proprietary license.
 *
 * The texts of the GNU Affero General Public License with an additional
 * permission and of our proprietary license can be found at and
 * in the LICENSE file you have received along with this program.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * "Shopware" is a registered trademark of shopware AG.
 * The licensing of the program under the AGPLv3 does not imply a
 * trademark license. Therefore any rights, title and interest in
 * our trademarks remain entirely with us.
 */

namespace ShopwarePlugins\Connect\Components;
use Shopware\Models\Customer\Group;

/**
 * Price gateway
 *
 * @category  Shopware
 * @package   Shopware\Components\Api
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
     * @return array
     * @throws \Zend_Db_Statement_Exception
     */
    public function countProductsWithoutConfiguredPrice(Group $group = null, $priceField)
    {
        if ($priceField == 'detailPurchasePrice') {
            $query = Shopware()->Db()->query("
                SELECT COUNT(sad.id)
                FROM s_articles_details sad
                LEFT JOIN s_plugin_connect_items spci ON sad.id = spci.article_detail_id
                WHERE spci.shop_id IS NULL AND sad.purchaseprice = 0
            ");
        } else {
            $query = Shopware()->Db()->query("
                SELECT COUNT(sad.id)
                FROM s_articles_details sad
                LEFT JOIN s_articles_prices sap ON sad.id = sap.articledetailsID AND sap.pricegroup = ?
                LEFT JOIN s_plugin_connect_items spci ON sad.id = spci.article_detail_id
                WHERE spci.shop_id IS NULL AND sap.{$priceField} IS NULL OR sap.{$priceField} = 0
            ", array($group->getKey()));
        }

        return (int)$query->fetchColumn();
    }

    /**
     * Returns count of product with
     * configured price
     *
     * @param Group $group
     * @param $priceField
     * @return array
     * @throws \Zend_Db_Statement_Exception
     */
    public function countProductsWithConfiguredPrice(Group $group = null, $priceField)
    {
        if ($priceField == 'detailPurchasePrice') {
            $query = Shopware()->Db()->query("
                SELECT COUNT(sad.id)
                FROM s_articles_details sad
                LEFT JOIN s_plugin_connect_items spci ON sad.id = spci.article_detail_id
                WHERE spci.shop_id IS NULL AND sad.purchaseprice != 0
            ");
        } else {
            $query = Shopware()->Db()->query("
                SELECT COUNT(sad.id)
                FROM s_articles_details sad
                LEFT JOIN s_articles_prices sap ON sad.id = sap.articledetailsID AND sap.pricegroup = ?
                LEFT JOIN s_plugin_connect_items spci ON sad.id = spci.article_detail_id
                WHERE spci.shop_id IS NULL AND sap.{$priceField} IS NOT NULL AND sap.{$priceField} != 0
            ", array($group->getKey()));
        }

        return (int)$query->fetchColumn();
    }

    /**
     * Returns count of product including variants for a group
     *
     * @param Group $group
     * @return array
     * @throws \Zend_Db_Statement_Exception
     */
    public function countProducts(Group $group = null)
    {
        $query = Shopware()->Db()->query("
            SELECT COUNT(sad.id)
            FROM s_articles_details sad
            LEFT JOIN s_articles_prices sap ON sad.id = sap.articledetailsID AND sap.pricegroup = ?
            LEFT JOIN s_plugin_connect_items spci ON sad.id = spci.article_detail_id
            WHERE spci.shop_id IS NULL"
        , array($group->getKey()));

        return (int)$query->fetchColumn();
    }
}