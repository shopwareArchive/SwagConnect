<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Bepado\SDK\Gateway;

use Bepado\SDK\Struct\Product;

/**
 * Gateway interface to maintain product hashes and exported products
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 * @api
 */
interface ProductGateway
{
    /**
     * Check if product has changed
     *
     * Return true, if product chenged since last check.
     *
     * @param string $id
     * @param string $hash
     * @return boolean
     */
    public function hasChanged($id, $hash);

    /**
     * Get IDs of all recorded products
     *
     * @return string[]
     */
    public function getAllProductIDs();
}
