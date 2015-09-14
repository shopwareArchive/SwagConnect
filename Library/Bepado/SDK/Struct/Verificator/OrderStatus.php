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
class OrderStatus extends Verificator
{
    protected function verifyDefault(VerificatorDispatcher $dispatcher, Struct $struct)
    {
        $requiredStrings = array('id', 'status');

        foreach ($requiredStrings as $requiredString) {
            if (!is_string($struct->$requiredString)) {
                throw new \Bepado\SDK\Exception\VerificationFailedException("OrderStatus#$requiredString must be a string.");
            }

            if (empty($struct->$requiredString)) {
                throw new \Bepado\SDK\Exception\VerificationFailedException("OrderStatus#$requiredString must be non-empty.");
            }
        }

        $allowedStates = array('open', 'in_process', 'delivered', 'canceled', 'error');

        if (!in_array($struct->status, $allowedStates)) {
            throw new \Bepado\SDK\Exception\VerificationFailedException(
                sprintf(
                    'Invalid order state given: %s. Expected one of: %s',
                    $struct->status,
                    implode(', ', $allowedStates)
                )
            );
        }

        if ($struct->tracking !== null) {
            if (!($struct->tracking instanceof \Bepado\SDK\Struct\Tracking)) {
                throw new \Bepado\SDK\Exception\VerificationFailedException(
                    "OrderStatus#tracking must be an instance of \\Bepado\\SDK\\Struct\\Tracking"
                );
            }

            $dispatcher->verify($struct->tracking);
        }
    }
}
