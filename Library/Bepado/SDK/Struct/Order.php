<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * @version $Revision$
 */

namespace Bepado\SDK\Struct;

use Bepado\SDK\Struct;

/**
 * Struct class representing an order
 *
 * @version $Revision$
 * @api
 */
class Order extends Struct
{
    /**
     * @var string
     */
    public $orderShop;

    /**
     * @var string
     */
    public $providerShop;

    /**
     * @var string
     */
    public $reservationId;

    /**
     * @var string
     */
    public $localOrderId;

    /**
     * @var float
     */
    public $shippingCosts;

    /**
     * @var OrderItem[]
     */
    public $products;

    /**
     * Delivery address
     *
     * @var Address
     */
    public $deliveryAddress;

    /**
     * Restores an order from a previously stored state array.
     *
     * @param array $state
     * @return \Bepado\SDK\Struct\Order
     */
    public static function __set_state(array $state)
    {
        return new Order($state);
    }
}
