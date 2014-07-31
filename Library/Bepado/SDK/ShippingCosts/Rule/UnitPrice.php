<?php
/**
 * This file is part of the Bepado Common Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Bepado\SDK\ShippingCosts\Rule;

use Bepado\SDK\ShippingCosts\Rule;
use Bepado\SDK\Struct\Order;
use Bepado\SDK\Struct\Shipping;
use Bepado\SDK\ShippingCosts\VatConfig;

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
     * Delivery work days
     *
     * @var int
     */
    public $deliveryWorkDays = 10;

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
     * @param VatConfig $vatConfig
     * @return Shipping
     */
    public function getShippingCosts(Order $order, VatConfig $vatConfig)
    {
        $units = array_reduce(
            $order->orderItems,
            function ($count, $orderItems) {
                return $count + $orderItems->count;
            },
            0
        );

        return new Shipping(
            array(
                'rule' => $this,
                'service' => $this->label,
                'deliveryWorkDays' => $this->deliveryWorkDays,
                'shippingCosts' => $this->price * $units / ($vatConfig->isNet ? 1 : 1 + $vatConfig->vat),
                'grossShippingCosts' => $this->price * $units * (!$vatConfig->isNet ? 1 : 1 + $vatConfig->vat),
            )
        );
    }
}
