<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Bepado\SDK\Struct;

use Bepado\SDK\Struct;

/**
 * Struct class representing an order item
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 * @api
 */
class OrderItem extends Struct
{
    /**
     * @var int
     */
    public $count;

    /**
     * @var Product
     */
    public $product;

    /**
     * Shipping information
     *
     * @var Shipping
     */
    public $shipping;

    /**
     * Restores an order item from a previously stored state array.
     *
     * @param array $state
     * @return \Bepado\SDK\Struct\OrderItem
     */
    public static function __set_state(array $state)
    {
        return new OrderItem($state);
    }
}
