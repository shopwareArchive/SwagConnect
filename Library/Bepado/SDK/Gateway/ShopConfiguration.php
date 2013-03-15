<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * @version 1.0.0snapshot201303151129
 */

namespace Bepado\SDK\Gateway;

use Bepado\SDK\Struct;

/**
 * Gateaway interface to maintain shop configurations
 *
 * @version 1.0.0snapshot201303151129
 * @api
 */
interface ShopConfiguration
{
    /**
     * Update shop configuration
     *
     * @param string $shopId
     * @param Struct\ShopConfiguration $configuration
     * @return void
     */
    public function setShopConfiguration($shopId, Struct\ShopConfiguration $configuration);

    /**
     * Get configuration for the given shop
     *
     * @param string $shopId
     * @return Struct\ShopConfiguration
     */
    public function getShopConfiguration($shopId);

    /**
     * Set category mapping
     *
     * @param array $categories
     * @return void
     */
    public function setCategories(array $categories);

    /**
     * Get category mapping
     *
     * @return array
     */
    public function getCategories();

    /**
     * Set own shop ID
     *
     * @param string $shopId
     * @return void
     */
    public function setShopId($shopId);

    /**
     * Get last shop verification date as Unix UTC timestamp
     *
     * @return int
     */
    public function getLastVerificationDate();

    /**
     * Get own shop ID
     *
     * @return string
     */
    public function getShopId();
}
