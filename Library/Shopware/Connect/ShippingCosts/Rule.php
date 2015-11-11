<?php
/**
 * This file is part of the Shopware Connect SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Shopware\Connect\ShippingCosts;

use Shopware\Connect\Struct\Order;
use Shopware\Connect\Struct;
use Shopware\Connect\ShippingCosts\VatConfig;

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
