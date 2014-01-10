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
     * Shop the order originates from.
     *
     * @var string
     */
    public $orderShop;

    /**
     * Shop providing the products, which are delivered to the customer.
     *
     * @var string
     */
    public $providerShop;

    /**
     * @var string
     */
    public $reservationId;

    /**
     * Order ID from the shop the order is placed with
     *
     * @var string
     */
    public $localOrderId;

    /**
     * Order ID from the product provider shop
     *
     * @var string
     */
    public $providerOrderId;

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
