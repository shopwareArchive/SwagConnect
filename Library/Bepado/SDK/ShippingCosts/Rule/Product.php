<?php
/**
 * This file is part of the Bepado Common Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Bepado\SDK\ShippingCosts\Rule;

use Bepado\SDK\ShippingCosts\Rule;
use Bepado\SDK\Struct\Order;
use Bepado\SDK\Struct\OrderItem;
use Bepado\SDK\Struct\Shipping;
use Bepado\SDK\ShippingCosts\VatConfig;

/**
 * Class: FixedPrice
 *
 * Rule for fixed price shipping costs for an order
 */
class Product extends Rule
{
    /**
     * @var string
     */
    public $country;

    /**
     * @var string
     */
    public $region;

    /**
     * @var string
     */
    public $zipRange;

    /**
     * @var int
     */
    public $deliveryWorkDays;

    /**
     * @var string
     */
    public $service;

    /**
     * @var float
     */
    public $price;

    /**
     * @var string
     */
    public $currency;

    /**
     * @var int
     */
    public $orderItemCount;

    /**
     * @var float
     */
    public $vat;

    /**
     * Check if shipping cost is applicable to given order
     *
     * @param Order $order
     * @return bool
     */
    public function isApplicable(Order $order)
    {
        if (isset($this->country) &&
            ($this->country !== $order->deliveryAddress->country)) {
            return false;
        }

        if (isset($this->zipRange) &&
            !fnmatch($this->zipRange, $order->deliveryAddress->zip)) {
            return false;
        }

        if (isset($this->region) &&
            ($this->region !== $order->deliveryAddress->state)) {
            return false;
        }

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
                'service' => $this->service,
                'deliveryWorkDays' => $this->deliveryWorkDays,
                'shippingCosts' => $this->price * $this->orderItemCount /
                    ($vatConfig->isNet ? 1 : 1 + $this->vat),
                'grossShippingCosts' => $this->price * $this->orderItemCount *
                    (!$vatConfig->isNet ? 1 : 1 + $this->vat),
            )
        );
    }
}
