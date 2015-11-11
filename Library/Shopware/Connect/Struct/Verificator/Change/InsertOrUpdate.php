<?php
/**
 * This file is part of the Shopware Connect SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Shopware\Connect\Struct\Verificator\Change;

use Shopware\Connect\Struct\Verificator\Change;
use Shopware\Connect\Struct\VerificatorDispatcher;
use Shopware\Connect\Struct;

use Shopware\Connect\Struct\Product;

/**
 * Visitor verifying integrity of struct classes
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */
class InsertOrUpdate extends Change
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
        parent::verifyDefault($dispatcher, $struct);

        if (!$struct->product instanceof Product) {
            throw new \RuntimeException('Property $product must be a Struct\Product.');
        }
        $dispatcher->verify($struct->product);
    }
}
