<?php
/**
 * This file is part of the Shopware Connect SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Shopware\Connect\Struct;

use Shopware\Connect\Struct;

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
     * @return \Shopware\Connect\Struct\OrderItem
     */
    public static function __set_state(array $state)
    {
        return new OrderItem($state);
    }
}
