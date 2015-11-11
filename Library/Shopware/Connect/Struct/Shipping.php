<?php
/**
 * This file is part of the Shopware Connect SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Shopware\Connect\Struct;

use Shopware\Connect\Struct;

class Shipping extends Struct
{
    /**
     * @var string
     */
    public $shopId;

    /**
     * @var \Shopware\Connect\ShippingCosts\Rule
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
