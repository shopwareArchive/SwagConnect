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

use Bepado\SDK\Exception\VerificationFailedException;

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
            throw new VerificationFailedException('Products MUST be an array.');
        }

        foreach ($struct->products as $product) {
            if (!$product instanceof OrderItem) {
                throw new VerificationFailedException(
                    'Products array MUST contain only instances of \\Bepado\\SDK\\Struct\\OrderItem.'
                );
            }

            $dispatcher->verify($product);
        }

        if (!$struct->deliveryAddress instanceof Address) {
            throw new VerificationFailedException('Delivery address MUST be an instance of \\Bepado\\SDK\\Struct\\Address.');
        }
        $dispatcher->verify($struct->deliveryAddress);

        if (!$struct->billingAddress instanceof Address) {
            throw new VerificationFailedException('Billing address MUST be an instance of \\Bepado\\SDK\\Struct\\Address.');
        }
        $dispatcher->verify($struct->billingAddress);

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
            throw new VerificationFailedException(
                sprintf(
                    'Invalid paymentType specified in order, must be one of: %s',
                    implode(", ", $paymentTypes)
                )
            );
        }

        if ($struct->shipping && !($struct->shipping instanceof \Bepado\SDK\Struct\Shipping)) {
            throw new VerificationFailedException('Shipping MUST be an instance of \\Bepado\\SDK\\Struct\\Shipping.');
        }
        $dispatcher->verify($struct->shipping);
    }
}
