<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * @version 1.1.133
 */

namespace Bepado\SDK\Struct\Verificator;

use Bepado\SDK\Struct\Verificator;
use Bepado\SDK\Struct\VerificatorDispatcher;
use Bepado\SDK\Struct;

/**
 * Visitor verifying integrity of struct classes
 *
 * @version 1.1.133
 */
class ShopConfiguration extends Verificator
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
        if ($struct->name === null) {
            throw new \RuntimeException('Property $name must be set.');
        }

        if ($struct->serviceEndpoint === null) {
            throw new \RuntimeException('Property $serviceEndpoint must be set.');
        }
    }
}
