<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * @version 1.0.129
 */

namespace Bepado\SDK\Gateway;

use Bepado\SDK\Struct\Product;

/**
 * Gateway interface to maintain product hashes and exported products
 *
 * @version 1.0.129
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
