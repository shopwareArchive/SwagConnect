<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * @version 1.1.133
 */

namespace Bepado\SDK\Gateway;

use Bepado\SDK\Struct;

/**
 * Gateaway interface to maintain shop configurations
 *
 * @version 1.1.133
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
     * Set the last revision of the category tree that the SDK has seen.
     *
     * @param string
     * @return void
     */
    public function setCategoriesLastRevision($revision);

    /**
     * Get the last revision of the category tree that the SDK has seen.
     *
     * @return string
     */
    public function getCategoriesLastRevision();

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
}
