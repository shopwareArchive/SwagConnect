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
use Bepado\SDK\ShippingCosts\Rule;

use Bepado\SDK\Exception\VerificationFailedException;

/**
 * Visitor verifying integrity of struct classes
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */
class ShippingRules extends Verificator
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
        if (!is_array($struct->rules)) {
            throw new VerificationFailedException('Rules MUST be an array.');
        }

        foreach ($struct->rules as $product) {
            if (!$product instanceof Rule\Product) {
                throw new VerificationFailedException(
                    'Rules array MUST contain only instances of \\Bepado\\SDK\\ShippingCosts\\Rule\\Product.'
                );
            }

            $dispatcher->verify($product);
        }
    }
}
