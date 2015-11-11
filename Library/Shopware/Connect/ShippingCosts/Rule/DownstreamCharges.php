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
 * Charges for Shippingcosts are calculated downstream and cannot be calculated beforehand.
 */
class DownstreamCharges extends Rule
{
    /**
     * @var string
     */
    public $label;

    /**
     * @var string
     */
    public $url;

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
                'deliveryWorkDays' => 10,
            )
        );
    }
}
