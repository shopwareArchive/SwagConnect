<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Bepado\SDK\Struct;

use Bepado\SDK\Struct;

/**
 * The shipping cost total for all the remote bepado orders.
 */
class TotalShippingCosts extends Struct
{
    /**
     * Key value pairs of shop ids and shipping costs.
     *
     * @var array<int,ShippingCosts>
     */
    public $shops = array();

    /**
     * @var bool
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
}
