<?php
/**
 * This file is part of the Shopware Connect SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Shopware\Connect;

use Shopware\Connect\ShippingCosts\Rule;
use Shopware\Connect\Gateway\ShippingCosts;
use Shopware\Connect\ShippingCosts\Rules;

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
     * @param \Shopware\Connect\ShippingCosts\Rules $shippingCostRules
     * @param \Shopware\Connect\Struct\Order $order
     *
     * @return \Shopware\Connect\Struct\Shipping
     */
    public function calculateShippingCosts(Rules $shippingCostRules, Struct\Order $order);
}
