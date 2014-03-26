<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Bepado\SDK\Struct\Verificator;

use Bepado\SDK\Struct\Verificator;
use Bepado\SDK\Struct\VerificatorDispatcher;
use Bepado\SDK\Struct;

use Bepado\SDK\Struct\OrderItem;
use Bepado\SDK\Struct\Address;

/**
 * Visitor verifying integrity of struct classes
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */
class Order extends Verificator
{
    /**
     * Method to verify a structs integrity
     *
     * Throws a RuntimeException if the struct does not verify.
     *
     * @param VerificatorDispatcher $dispatcher
     * @param Struct $struct
     * @return void
     */
    public function verify(VerificatorDispatcher $dispatcher, Struct $struct)
    {
        if (!is_array($struct->products)) {
            throw new \RuntimeException('Products MUST be an array.');
        }

        foreach ($struct->products as $product) {
            if (!$product instanceof OrderItem) {
                throw new \RuntimeException(
                    'Products array MUST contain only instances of \\Bepado\\SDK\\Struct\\OrderItem.'
                );
            }

            $dispatcher->verify($product);
        }

        if (!$struct->deliveryAddress instanceof Address) {
            throw new \RuntimeException('Delivery address MUST be an instance of \\Bepado\\SDK\\Struct\\Address.');
        }
        $dispatcher->verify($struct->deliveryAddress);

        $paymentTypes = array(
            Struct\Order::PAYMENT_ADVANCE,
            Struct\Order::PAYMENT_INVOICE,
            Struct\Order::PAYMENT_DEBIT,
            Struct\Order::PAYMENT_CREDITCARD,
            Struct\Order::PAYMENT_PROVIDER,
            Struct\Order::PAYMENT_UNKNOWN,
            Struct\Order::PAYMENT_OTHER,
        );

        if (!in_array($struct->paymentType, $paymentTypes)) {
            throw new \RuntimeException(
                sprintf(
                    'Invalid paymentType specified in order, must be one of: %s',
                    implode(", ", $paymentTypes)
                )
            );
        }

        if ($struct->shippingRule && !($struct->shipingRule instanceof \Bepado\SDK\ShippingCosts\Rule)) {
            throw new \RuntimeException('Shipping Rule MUST be an instance of \\Bepado\\SDK\\ShippingCosts\\Rule.');
        }
    }
}
