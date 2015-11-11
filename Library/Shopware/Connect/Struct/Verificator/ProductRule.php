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

use Shopware\Connect\Exception\VerificationFailedException;

/**
 * Visitor verifying integrity of struct classes
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */
class ProductRule extends Verificator
{
    /**
     * Valid currencies
     *
     * @var string[]
     */
    private $validCurrencies = array('EUR');

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
        if (!in_array($struct->currency, $this->validCurrencies)) {
            throw new VerificationFailedException(
                'Currently supported currencies: ' . implode(', ', $this->validCurrencies)
            );
        }

        if (!is_null($struct->deliveryWorkDays) &&
            !($struct->deliveryWorkDays > 0)) {
            throw new VerificationFailedException(
                'Delivery work days must be a positive number'
            );
        }
    }
}
