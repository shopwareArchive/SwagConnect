<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Bepado\SDK\Struct;

use Bepado\SDK\Struct;

class Shipping extends Struct
{
    /**
     * @var string
     */
    public $shopId;

    /**
     * @var \Bepado\SDK\ShippingCosts\Rule
     */
    public $rule;

    /**
     * @return bool
     */
    public $isShippable = true;

    /**
     * Net shipping costs.
     *
     * @var float
     */
    public $shippingCosts;

    /**
     * Gross shipping costs with VAT applied.
     *
     * @var float
     */
    public $grossShippingCosts;

    /**
     * Delivery time in days
     *
     * @var int
     */
    public $deliveryWorkDays;

    /**
     * Delivery service
     *
     * @var string
     */
    public $service;
}
