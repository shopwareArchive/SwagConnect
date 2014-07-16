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

/**
 * Visitor verifying integrity of struct classes
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
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
                throw new \Bepado\SDK\Exception\VerificationFailedException("Tracking#$string must be a string.");
            }
        }
    }
}
