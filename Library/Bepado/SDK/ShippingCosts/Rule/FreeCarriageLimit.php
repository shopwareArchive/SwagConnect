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
 * Free shipping costs when the order exceeds a specific monetary volume.
 */
class FreeCarriageLimit extends Rule
{
    /**
     * @var float
     */
    public $freeLimit;

    /**
     * @var \Bepado\SDK\ShippingCosts\Rule
     */
    public $delegatee;

    /**
     * Check if shipping cost is applicable to given order
     *
     * @param Order $order
     * @return bool
     */
    public function isApplicable(Order $order)
    {
        return $this->delegatee->isApplicable($order);
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
        $total = 0;

        foreach ($order->orderItems as $item) {
            $total += ($item->count * $item->product->purchasePrice * (1 + $item->product->vat));
        }

        if ($total >= $this->freeLimit) {
            return 0;
        }

        return $this->delegatee->getShippingCosts($order);
    }

    /**
     * If processing should stop after this rule
     *
     * @param Order $order
     * @return bool
     */
    public function shouldStopProcessing(Order $order)
    {
        return $this->delegatee->shouldStopProcessing($order);
    }
}
