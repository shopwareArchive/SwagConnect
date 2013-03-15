<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * @version 1.0.0snapshot201303151129
 */

namespace Bepado\SDK\Struct;

use Bepado\SDK\Struct;

/**
 * Struct class representing an order item
 *
 * @version 1.0.0snapshot201303151129
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
