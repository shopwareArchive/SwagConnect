<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * @version 1.0.129
 */

namespace Bepado\SDK\Struct\Verificator;

use Bepado\SDK\Struct\Verificator;
use Bepado\SDK\Struct\VerificatorDispatcher;
use Bepado\SDK\Struct;

/**
 * Visitor verifying integrity of struct classes
 *
 * @version 1.0.129
 */
class OrderStatus extends Verificator
{
    public function verify(VerificatorDispatcher $dispatcher, Struct $struct)
    {
        $requiredStrings = array('id', 'status');

        foreach ($requiredStrings as $requiredString) {
            if (!is_string($struct->$requiredString)) {
                throw new \RuntimeException("OrderStatus#$requiredString must be a string.");
            }

            if (empty($struct->$requiredString)) {
                throw new \RuntimeException("OrderStatus#$requiredString must be non-empty.");
            }
        }

        $allowedStates = array('open', 'in_process', 'delivered', 'canceled', 'error');

        if (!in_array($struct->status, $allowedStates)) {
            throw new \RuntimeException(
                sprintf(
                    'Invalid order state given: %s. Expected one of: %s',
                    $struct->status,
                    implode(', ', $allowedStates)
                )
            );
        }

        if ($struct->tracking !== null) {
            if (!($struct->tracking instanceof \Bepado\SDK\Struct\Tracking)) {
                throw new \RuntimeException(
                    "OrderStatus#tracking must be an instance of \\Bepado\\SDK\\Struct\\Tracking"
                );
            }

            $dispatcher->verify($struct->tracking);
        }
    }
}
