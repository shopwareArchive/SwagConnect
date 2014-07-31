<?php
/**
 * This file is part of the Bepado Common Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Bepado\SDK\ShippingCosts;

use Bepado\SDK\Struct\Order;
use Bepado\SDK\Struct;
use Bepado\SDK\ShippingCosts\VatConfig;

/**
 * Class: Rule
 *
 * Base class for rules to calculate shipping costs for a given order.
 */
abstract class Rule extends Struct
{
    /**
     * Check if shipping cost is applicable to given order
     *
     * @param Order $order
     * @return bool
     */
    abstract public function isApplicable(Order $order);

    /**
     * Get shipping costs for order
     *
     * Returns the net shipping costs.
     *
     * @param Order $order
     * @param VatConfig $vatConfig
     * @return Shipping
     */
    abstract public function getShippingCosts(Order $order, VatConfig $vatConfig);

    /**
     * Restore rule after var_export
     *
     * @param array $values
     * @return Rule
     */
    public static function __set_state(array $values)
    {
        return new static($values);
    }
}
