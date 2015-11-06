<?php
/**
 * This file is part of the Shopware Connect SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Shopware\Connect;

/**
 * Shop factory
 *
 * Constructs gateways to interact with other shops
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
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
