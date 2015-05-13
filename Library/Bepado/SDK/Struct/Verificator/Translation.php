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
class Translation extends Verificator
{
    /**
     * Method to verify a structs integrity
     *
     * Throws a RuntimeException if the struct does not verify.
     *
     * @param \Bepado\SDK\Struct\VerificatorDispatcher $dispatcher
     * @param \Bepado\SDK\Struct $struct
     * @return void
     * @throws \RuntimeException
     */
    public function verify(VerificatorDispatcher $dispatcher, Struct $struct)
    {
        /* @var $struct \Bepado\SDK\Struct\Translation */

        foreach (array(
            'title',
            'shortDescription',
            'longDescription',
            ) as $property) {
            if (@iconv('UTF-8', 'UTF-8', $struct->$property) != $struct->$property) {
                throw new \Bepado\SDK\Exception\VerificationFailedException("Property $property MUST be UTF-8 encoded.");
            }
        }

        foreach (array(
            'variantLabels',
            'variantValues',
            ) as $property) {
            if (!is_array($struct->$property)) {
                throw new \Bepado\SDK\Exception\VerificationFailedException("Property $property MUST be an array or null.");
            }

            foreach ($struct->$property as $key => $value) {
                if (!is_string($key)) {
                    throw new \Bepado\SDK\Exception\VerificationFailedException(
                        "Property $property MUST only contain string keys."
                    );
                }
                if (!is_string($value)) {
                    throw new \Bepado\SDK\Exception\VerificationFailedException(
                        "Property $property MUST only contain string values."
                    );
                }
            }
        }
    }
}
