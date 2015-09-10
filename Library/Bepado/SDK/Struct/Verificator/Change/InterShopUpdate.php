<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Bepado\SDK\Struct\Verificator\Change;

use Bepado\SDK\Struct\Verificator\Change;
use Bepado\SDK\Struct\VerificatorDispatcher;
use Bepado\SDK\Struct;

use Bepado\SDK\Struct\Product;

/**
 * Visitor verifying integrity of struct classes
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */
class InterShopUpdate extends Change
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
        if ($struct->sourceId === null) {
            throw new \RuntimeException('Property $sourceId must be set.');
        }

        if (!$struct->product instanceof Product) {
            throw new \RuntimeException('Property $product must be a Struct\Product.');
        }
        $dispatcher->verify($struct->product);

        if (!$struct->oldProduct instanceof Product) {
            throw new \RuntimeException('Property $oldProduct must be a Struct\Product.');
        }
        $dispatcher->verify($struct->oldProduct);
    }
}
