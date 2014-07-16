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
     * @return float
     */
    public function getShippingCosts(Order $order)
    {
        return new Shipping(
            array(
                'rule' => $this,
                'service' => $this->service,
                'deliveryWorkDays' => $this->deliveryWorkDays,
                'isShippable' => true,
                'shippingCosts' => $this->price * $this->orderItemCount,
                'grossShippingCosts' => $this->price * $this->orderItemCount * (1 + $this->vat),
            )
        );
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
