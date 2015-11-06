<?php
/**
 * This file is part of the Shopware Connect SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Shopware\Connect\Struct;

use Shopware\Connect\Struct;

/**
 * Struct class representing an order
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
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
     * General shipping information
     *
     * @var Shipping
     */
    public $shipping;

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
     * Billing address
     *
     * @var Address
     */
    public $billingAddress;

    /**
     * __construct
     *
     * @param array $values
     * @return void
     */
    public function __construct(array $values = array())
    {
        $this->shipping = new Shipping();

        parent::__construct($values);
    }

    /**
     * Restores an order from a previously stored state array.
     *
     * @param array $state
     * @return \Shopware\Connect\Struct\Order
     */
    public static function __set_state(array $state)
    {
        return new Order($state);
    }

    /**
     * Compatibility wrapper for deprecated property names
     *
     * @param string $property
     * @return mixed
     */
    public function &__get($property)
    {
        switch ($property) {
            case 'products':
                return $this->orderItems;

            case 'shippingRule':
                return $this->shipping->rule;

            case 'shippingCosts':
                return $this->shipping->shippingCosts;

            case 'grossShippingCosts':
                return $this->shipping->grossShippingCosts;

            default:
                return parent::__get($property);
        }
    }

    /**
     * Compatibility wrapper for deprecated property names
     *
     * @param string $property
     * @param mixed $value
     * @return void
     */
    public function __set($property, $value)
    {
        switch ($property) {
            case 'products':
                $this->orderItems = $value;
                break;

            case 'shippingRule':
                $this->shipping->rule = $value;
                break;

            case 'shippingCosts':
                $this->shipping->shippingCosts = $value;
                break;

            case 'grossShippingCosts':
                $this->shipping->grossShippingCosts = $value;
                break;

            default:
                return parent::__set($property, $value);
        }
    }
}
