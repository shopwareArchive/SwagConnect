<?php

namespace Shopware\Bepado\Components;

/**
 * This service is will return all known shipping costs for the current shop.
 *
 * It should be replaced by the bepado shipping cost interface once there is one in the SDK
 *
 * Class ShippingCosts
 * @package Shopware\Bepado\Components
 */
class ShippingCostBridge
{
    /** @var  \PDO|\Enlight_Components_Db_Adapter_Pdo_Mysql */
    protected $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    protected function getLocalShopId()
    {
        $result = $this->db->fetchOne('SELECT s_config FROM `bepado_shop_config` WHERE `s_shop` = "_self_"');
        return $result;
    }

    protected function getShippingCosts()
    {
        $result = $this->db->fetchAssoc(
            'SELECT `sc_from_shop`, `sc_shipping_costs` FROM `bepado_shipping_costs` WHERE `sc_to_shop`=?',
            array($this->getLocalShopId())
        );

        return array_map(function($cost) {
            return unserialize($cost['sc_shipping_costs']);
        }, $result);

    }

    public function getShippingCostsForCurrentShop()
    {

        $rules = $this->getShippingCosts();

        return $rules;
    }
}