<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Bepado\SDK\Gateway;

use Bepado\SDK\Struct;

/**
 * Gateaway interface to maintain shop configurations
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
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
     * @throws \RuntimeException If shop does not exist in configuration.
     * @return Struct\ShopConfiguration
     */
    public function getShopConfiguration($shopId);

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
     * Returns null if the shop ID is not set, yet.
     *
     * @return string|null
     */
    public function getShopId();

    /**
     * Get all connected shop ids.
     *
     * @return array<string>
     */
    public function getConnectedShopIds();

    /**
     * Set all the enabled features.
     *
     * @param array<string>
     */
    public function setEnabledFeatures(array $features);

    /**
     * Is a feature enabled?
     *
     * @param string $featureName
     * @return bool
     */
    public function isFeatureEnabled($feature);

    /**
     * Set the shops billing address used in orders.
     *
     * @param \Bepado\SDK\Struct\Address $address
     */
    public function setBillingAddress(Struct\Address $address);

    /**
     * @return \Bepado\SDK\Struct\Address
     */
    public function getBillingAddress();

    /**
     * Set a configuration value.
     *
     * @param mixed $key
     * @param mixed $config
     * @return bool
     */
    public function setConfig($key, $value);

    /**
     * Retrieve a configuration value.
     *
     * @param $key
     * @return null|string
     */
    public function getConfig($shopId);
}
