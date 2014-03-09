<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * @version 1.1.141
 */

namespace Bepado\SDK;

use Bepado\SDK\ShippingCosts\Rule;

/**
 * Shipping cost calculator
 *
 * @version 1.1.141
 */
interface ShippingCostCalculator
{
    /**
     * @return \Bepado\SDK\Struct\TotalShippingCosts
     */
    public function calculateShippingCosts(Struct\Order $order);
}
