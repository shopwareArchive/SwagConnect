<?php
/**
 * This file is part of the Shopware Connect SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Shopware\Connect\Gateway;

use Shopware\Connect\Struct\Product;

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
