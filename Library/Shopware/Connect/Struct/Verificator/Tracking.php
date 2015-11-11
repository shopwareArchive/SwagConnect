<?php
/**
 * This file is part of the Shopware Connect SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Shopware\Connect\Struct\Verificator;

use Shopware\Connect\Struct\Verificator;
use Shopware\Connect\Struct\VerificatorDispatcher;
use Shopware\Connect\Struct;

/**
 * Visitor verifying integrity of struct classes
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */
class Tracking extends Verificator
{
    protected function verifyDefault(VerificatorDispatcher $dispatcher, Struct $struct)
    {
        $strings = array('id', 'url', 'vendor');

        foreach ($strings as $string) {
            if ($struct->$string === null) {
                continue;
            }

            if (!is_string($struct->$string)) {
                throw new \Shopware\Connect\Exception\VerificationFailedException("Tracking#$string must be a string.");
            }
        }
    }
}
