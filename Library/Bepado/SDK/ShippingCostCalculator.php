<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Bepado\SDK;

use Bepado\SDK\ShippingCosts\Rule;
use Bepado\SDK\Gateway\ShippingCosts;
use Bepado\SDK\ShippingCosts\Rules;

/**
 * Shipping cost calculator
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */
interface ShippingCostCalculator
{
    /**
     * Get shipping costs for order
     *
     * @param \Bepado\SDK\ShippingCosts\Rules $shippingCostRules
     * @param \Bepado\SDK\Struct\Order $order
     *
     * @return \Bepado\SDK\Struct\Shipping
     */
    public function calculateShippingCosts(Rules $shippingCostRules, Struct\Order $order);
}
