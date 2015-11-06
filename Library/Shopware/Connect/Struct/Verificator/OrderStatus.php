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
class OrderStatus extends Verificator
{
    protected function verifyDefault(VerificatorDispatcher $dispatcher, Struct $struct)
    {
        $requiredStrings = array('id', 'status');

        foreach ($requiredStrings as $requiredString) {
            if (!is_string($struct->$requiredString)) {
                throw new \Shopware\Connect\Exception\VerificationFailedException("OrderStatus#$requiredString must be a string.");
            }

            if (empty($struct->$requiredString)) {
                throw new \Shopware\Connect\Exception\VerificationFailedException("OrderStatus#$requiredString must be non-empty.");
            }
        }

        $allowedStates = array('open', 'in_process', 'delivered', 'canceled', 'error');

        if (!in_array($struct->status, $allowedStates)) {
            throw new \Shopware\Connect\Exception\VerificationFailedException(
                sprintf(
                    'Invalid order state given: %s. Expected one of: %s',
                    $struct->status,
                    implode(', ', $allowedStates)
                )
            );
        }

        if ($struct->tracking !== null) {
            if (!($struct->tracking instanceof \Shopware\Connect\Struct\Tracking)) {
                throw new \Shopware\Connect\Exception\VerificationFailedException(
                    "OrderStatus#tracking must be an instance of \\Shopware\\Connect\\Struct\\Tracking"
                );
            }

            $dispatcher->verify($struct->tracking);
        }
    }
}
