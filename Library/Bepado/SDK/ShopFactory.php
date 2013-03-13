<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * @version 1.0.0snapshot201303061109
 */

namespace Bepado\SDK;

/**
 * Shop factory
 *
 * Constructs gateways to interact with other shops
 *
 * @version 1.0.0snapshot201303061109
 */
abstract class ShopFactory
{
    /**
     * Get shop gateway for shop
     *
     * @param string $shopId
     * @return ShopGateway
     */
    abstract public function getShopGateway($shopId);
}
