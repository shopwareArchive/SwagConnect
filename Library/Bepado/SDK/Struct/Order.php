<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * @version 1.1.141
 */

namespace Bepado\SDK\Struct;

use Bepado\SDK\Struct;

/**
 * Struct class representing an order
 *
 * @version 1.1.141
 * @api
 */
class Order extends Struct
{
    /**
     * Describe different Payment Types used for the order.
     */
    const PAYMENT_ADVANCE = 'advance';
    const PAYMENT_INVOICE = 'invoice';
    const PAYMENT_DEBIT = 'debit';
    const PAYMENT_CREDITCARD = 'creditcard';
    const PAYMENT_PROVIDER = 'provider';
    const PAYMENT_OTHER = 'other';
    const PAYMENT_UNKNOWN = 'unknown';

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
     * The delivery type that is used for this order.
     *
     * Is calculated internally by the SDK.
     *
     * @var \Bepado\SDK\ShippingCosts\Rule
     */
    public $shippingRule;

    /**
     * The payment type that is used for this order.
     *
     * @var string
     */
    public $paymentType = self::PAYMENT_UNKNOWN;

    /**
     * @var OrderItem[]
     */
    public $orderItems;

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

    public function &__get($property)
    {
        switch ($property) {
            case 'products':
                return $this->orderItems;

            default:
                return parent::__get($property);
        }
    }

    public function __set($property, $value)
    {
        switch ($property) {
            case 'products':
                $this->orderItems = $value;
                break;

            default:
                return parent::__set($property, $value);
        }
    }
}
