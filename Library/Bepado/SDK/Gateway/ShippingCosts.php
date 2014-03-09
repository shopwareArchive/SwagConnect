<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * @version 1.1.141
 */

namespace Bepado\SDK\Gateway;

use Bepado\SDK\Struct;
use Bepado\SDK\ShippingCosts\Rules;

/**
 * Gateaway interface to maintain shipping costs
 *
 * @version 1.1.141
 * @api
 */
interface ShippingCosts
{
    /**
     * Get last revision
     *
     * @return string
     */
    public function getLastShippingCostsRevision();

    /**
     * Store shop shipping costs
     *
     * @param string $fromShop
     * @param string $toShop
     * @param string $revision
     * @param \Bepado\SDK\ShippingCosts\Rules $shippingCosts
     * @return void
     */
    public function storeShippingCosts($fromShop, $toShop, $revision, Rules $shippingCosts);

    /**
     * Get shop shipping costs
     *
     * @param string $fromShop
     * @param string $toShop
     * @return \Bepado\SDK\ShippingCosts\Rules
     */
    public function getShippingCosts($fromShop, $toShop);
}
