<?php

namespace Shopware\Bepado\Components\Utils;

/**
 * Util to check orders for bepado products
 *
 * Class BepadoOrderUtil
 * @package Shopware\Bepado\Components\Utils
 */
class BepadoOrderUtil
{

    /**
     * Returns a list of bepado orders, their shop_id and the remote order_id
     * @param $orderIds
     * @return mixed
     */
    function getRemoteBepadoOrders($orderIds) {
        // This will apply for the fromShop
        $sql = 'SELECT orderID, bepado_shop_id, bepado_order_id
        FROM s_order_attributes
        WHERE orderID IN (' . implode(', ', $orderIds) . ')
        AND bepado_shop_id IS NOT NULL
        ';
        return  Shopware()->Db()->fetchAll($sql);
    }

    /**
     * Does a given order have bepado products?
     *
     * @param $orderId
     * @return bool
     */
    function hasRemoteOrderBepadoProducts($orderId)
    {
        $orders = $this->getRemoteBepadoOrders(array($orderId));
        return !empty($orders);
    }

    /**
     * Returns a list of bepado orders and their shop_id
     *
     * @param $orderIds
     * @return mixed
     */
    function getLocalBepadoOrders($orderIds)
    {
        // This will apply for orders with remote bepado products in it
        $sql = 'SELECT oa.orderID, bi.shop_id as bepado_shop_id,  "remote" as bepado_order_id

        FROM s_order_attributes oa

        INNER JOIN s_order_details od
        ON od.orderID = oa.orderID

        INNER JOIN s_articles_details ad
        ON ad.articleID = od.articleID
        AND ad.kind=1

        INNER JOIN s_plugin_bepado_items bi
        ON bi.article_detail_id=ad.id
        AND bi.shop_id IS NOT NULL

        WHERE oa.orderID In (' . implode(', ', $orderIds) . ')
        ';

        return Shopware()->Db()->fetchAll($sql);
    }

    /**
     * Does a given order have bepado products?
     *
     * @param $orderId
     * @return bool
     */
    function hasLocalOrderBepadoProducts($orderId)
    {
        $orders = $this->getLocalBepadoOrders(array($orderId));
        return !empty($orders);
    }
}