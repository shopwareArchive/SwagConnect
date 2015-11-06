<?php
/**
 * This file is part of the Shopware Connect SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Shopware\Connect\ShippingCosts\Rule;

use Shopware\Connect\ShippingCosts\Rule;
use Shopware\Connect\Struct\Order;
use Shopware\Connect\ShippingCosts\VatConfig;

/**
 * Rule decorator, which applies the delegatee only if a given basket value is
 * reached.
 */
class MinimumBasketValue extends Rule
{
    /**
     * Minimum order value to apply delegatee
     *
     * @var float
     */
    public $minimum;

    /**
     * @var \Shopware\Connect\ShippingCosts\Rule
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
        $total = 0;
        foreach ($order->orderItems as $item) {
            $total += ($item->count * $item->product->purchasePrice * (1 + $item->product->vat));
        }

        if ($total < $this->minimum) {
            return false;
        }

        return $this->delegatee->isApplicable($order);
    }

    /**
     * Get shipping costs for order
     *
     * Returns the net shipping costs.
     *
     * @param Order $order
     * @param VatConfig $vatConfig
     * @return Shipping
     */
    public function getShippingCosts(Order $order, VatConfig $vatConfig)
    {
        return $this->delegatee->getShippingCosts($order, $vatConfig);
    }
}
