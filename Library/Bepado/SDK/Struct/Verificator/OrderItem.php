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

use Bepado\SDK\Struct\Product;

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
    public function verify(VerificatorDispatcher $dispatcher, Struct $struct)
    {
        if (!is_int($struct->count) ||
            $struct->count <= 0) {
            throw new \Bepado\SDK\Exception\VerificationFailedException('Count MUST be a positive integer.');
        }

        if (!$struct->product instanceof Product) {
            throw new \Bepado\SDK\Exception\VerificationFailedException('Product MUST be an instance of \\Bepado\\SDK\\Struct\\Product.');
        }
        $dispatcher->verify($struct->product);
    }
}
