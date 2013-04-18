<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * @version $Revision$
 */

namespace Bepado\SDK\Struct\Verificator\Change;

use Bepado\SDK\Struct\Verificator\Change;
use Bepado\SDK\Struct\VerificatorDispatcher;
use Bepado\SDK\Struct;

use Bepado\SDK\Struct\Product;

/**
 * Visitor verifying integrity of struct classes
 *
 * @version $Revision$
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
    public function verify(VerificatorDispatcher $dispatcher, Struct $struct)
    {
        parent::verify($dispatcher, $struct);

        if (!$struct->product instanceof Product) {
            throw new \RuntimeException('Property $product must be a Struct\Product.');
        }
        $dispatcher->verify($struct->product);
    }
}
