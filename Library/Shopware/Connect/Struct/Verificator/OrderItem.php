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
class OrderItem extends Verificator
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
        if (!is_int($struct->count) ||
            $struct->count <= 0) {
            throw new \Shopware\Connect\Exception\VerificationFailedException('Count MUST be a positive integer.');
        }

        if (!$struct->product instanceof Struct\Product) {
            throw new \Shopware\Connect\Exception\VerificationFailedException('Product MUST be an instance of \\Shopware\\Connect\\Struct\\Product.');
        }
        $dispatcher->verify($struct->product);
    }
}
