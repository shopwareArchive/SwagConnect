<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * @version 1.1.142
 */

namespace Bepado\SDK\Struct\Verificator;

use Bepado\SDK\Struct\Verificator;
use Bepado\SDK\Struct\VerificatorDispatcher;
use Bepado\SDK\Struct;

/**
 * Visitor verifying integrity of struct classes
 *
 * @version 1.1.142
 */
class Tracking extends Verificator
{
    public function verify(VerificatorDispatcher $dispatcher, Struct $struct)
    {
        $strings = array('id', 'url', 'vendor');

        foreach ($strings as $string) {
            if ($struct->$string === null) {
                continue;
            }

            if (!is_string($struct->$string)) {
                throw new \RuntimeException("Tracking#$string must be a string.");
            }
        }
    }
}
