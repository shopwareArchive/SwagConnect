<?php
/**
 * This file is part of the Shopware Connect SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Shopware\Connect\Struct\Verificator;

use Shopware\Connect\Struct\Verificator;
use Shopware\Connect\Struct\VerificatorDispatcher;
use Shopware\Connect\Struct;

/**
 * Visitor verifying integrity of struct classes
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */
class Reservation extends Verificator
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
    protected function verifyDefault(VerificatorDispatcher $dispatcher, Struct $struct)
    {
        if (!is_array($struct->messages)) {
            throw new \Shopware\Connect\Exception\VerificationFailedException('$messages MUST be an array.');
        }
        foreach ($struct->messages as $shopId => $messages) {
            if (!is_array($messages)) {
                throw new \Shopware\Connect\Exception\VerificationFailedException('$messages MUST be an array.');
            }

            foreach ($messages as $message) {
                if (!$message instanceof Struct\Message) {
                    throw new \Shopware\Connect\Exception\VerificationFailedException('$message MUST be an instance of \\Shopware\\Connect\\Struct\\Message.');
                }
                $dispatcher->verify($message);
            }
        }

        if (!is_array($struct->orders)) {
            throw new \Shopware\Connect\Exception\VerificationFailedException('$orders MUST be an array.');
        }
        foreach ($struct->orders as $order) {
            if (!$order instanceof Struct\Order) {
                throw new \Shopware\Connect\Exception\VerificationFailedException('$orders MUST be an instance of \\Shopware\\Connect\\Struct\\Order.');
            }
            $dispatcher->verify($order);
        }
    }
}
