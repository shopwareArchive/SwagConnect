<?php
/**
 * This file is part of the Shopware Connect SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Shopware\Connect\ShippingCostCalculator;

use Shopware\Connect\Struct\Shipping;

abstract class Aggregator
{
    /**
     * Aggregate shipping costs
     *
     * Aggregate shipping costs of order items and return the sum of all
     * shipping costs.
     *
     * Optionally provide return object.
     *
     * @param Shipping[] $shippings
     * @param Shipping $shipping
     * @return Shipping
     */
    abstract public function aggregateShippingCosts(array $shippings, Shipping $shipping = null);
}
