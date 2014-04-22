<?php
/**
 * This file is part of the Bepado Common Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Bepado\SDK\ShippingCosts\Rule;

use Bepado\SDK\ShippingCosts\Rule;
use Bepado\SDK\Struct\Order;

/**
 * Price is multiplied by unit.
 */
class UnitPrice extends Rule
{
    /**
     * @var string
     */
    public $label;

    /**
     * @var float
     */
    public $price = 0;

    /**
     * Check if shipping cost is applicable to given order
     *
     * @param Order $order
     * @return bool
     */
    public function isApplicable(Order $order)
    {
        return true;
    }

    /**
     * Get shipping costs for order
     *
     * Returns the net shipping costs.
     *
     * @param Order $order
     * @return float
     */
    public function getShippingCosts(Order $order)
    {
        $units = array_reduce(
            $order->orderItems,
            function ($count, $orderItems) {
                return $count + $orderItems->count;
            },
            0
        );

        return $this->price * $units;
    }

    /**
     * If processing should stop after this rule
     *
     * @param Order $order
     * @return bool
     */
    public function shouldStopProcessing(Order $order)
    {
        return true;
    }
}
