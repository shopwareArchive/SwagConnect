<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * @version 1.0.0snapshot201303151129
 */

namespace Bepado\SDK\Struct\Verificator\Change;

use Bepado\SDK\Struct\Verificator\Change;
use Bepado\SDK\Struct\VerificatorDispatcher;
use Bepado\SDK\Struct;

use Bepado\SDK\Struct\Product;

/**
 * Visitor verifying integrity of struct classes
 *
 * @version 1.0.0snapshot201303151129
 */
class InterShopDelete extends Change
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
        if ($struct->sourceId === null) {
            throw new \RuntimeException('Property $sourceId must be set.');
        }
    }
}
