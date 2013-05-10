<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * @version $Revision$
 */

namespace Bepado\SDK\Struct;

use Bepado\SDK\Struct;

/**
 * Visitor verifying integrity of struct classes
 *
 * @version $Revision$
 */
abstract class Verificator
{
    /**
     * Method to verify a structs integrity
     *
     * Throws a RuntimeException if the struct does not verify.
     *
     * @param VerificatorDispatcher $dispatcher
     * @param Struct $struct
     * @return void
     * @throws RuntimeException if the struct is not valid
     */
    abstract public function verify(VerificatorDispatcher $dispatcher, Struct $struct);
}
