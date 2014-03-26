<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Bepado\SDK;

use Bepado\SDK\ShippingCosts\Rule;
use Bepado\SDK\Gateway\ShippingCosts;

/**
 * Shipping cost calculator
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */
interface ShippingCostCalculator
{
    /**
     * @param \Bepado\SDK\Struct\Order
     * @param string $type
     *
     * @return \Bepado\SDK\Struct\TotalShippingCosts
     */
    public function calculateShippingCosts(Struct\Order $order, $type);
}
