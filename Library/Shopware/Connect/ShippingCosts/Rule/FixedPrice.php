<?php
/**
 * This file is part of the Shopware Connect SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Shopware\Connect\ShippingCosts\Rule;

use Shopware\Connect\ShippingCosts\Rule;
use Shopware\Connect\Struct\Order;
use Shopware\Connect\Struct\Shipping;
use Shopware\Connect\ShippingCosts\VatConfig;

/**
 * Class: FixedPrice
 *
 * Rule for fixed price shipping costs for an order
 */
class FixedPrice extends Rule
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
        return new Shipping(
            array(
                'rule' => $this,
                'service' => $this->label,
                'deliveryWorkDays' => $this->deliveryWorkDays,
                'shippingCosts' => $this->price / ($vatConfig->isNet ? 1 : 1 + $vatConfig->vat),
                'grossShippingCosts' => $this->price * (!$vatConfig->isNet ? 1 : 1 + $vatConfig->vat),
            )
        );
    }
}
