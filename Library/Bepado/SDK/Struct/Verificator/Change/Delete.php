<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * @version 1.1.141
 */

namespace Bepado\SDK\Struct\Verificator\Change;

use Bepado\SDK\Struct\Verificator\Change;
use Bepado\SDK\Struct\VerificatorDispatcher;
use Bepado\SDK\Struct;

/**
 * Visitor verifying integrity of struct classes
 *
 * @version 1.1.141
 */
class Delete extends Change
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
        parent::verify($dispatcher, $struct);

        if ($struct->shopId === null) {
            throw new \RuntimeException('Property $shopId must be set.');
        }
    }
}
