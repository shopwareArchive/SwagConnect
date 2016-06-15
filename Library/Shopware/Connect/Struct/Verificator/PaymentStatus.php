<?php

namespace Shopware\Connect\Struct\Verificator;

use Shopware\Connect\Struct;
use Shopware\Connect\Struct\Verificator;
use Shopware\Connect\Struct\VerificatorDispatcher;

class PaymentStatus extends Verificator
{
    protected function verifyDefault(VerificatorDispatcher $dispatcher, Struct $struct)
    {
        $requiredStrings = array('localOrderId', 'paymentStatus');

        foreach ($requiredStrings as $requiredString) {
            if (!is_string($struct->$requiredString)) {
                throw new \Shopware\Connect\Exception\VerificationFailedException("OrderStatus#$requiredString must be a string.");
            }

            if (empty($struct->$requiredString)) {
                throw new \Shopware\Connect\Exception\VerificationFailedException("OrderStatus#$requiredString must be non-empty.");
            }
        }

        $allowedStates = array(
            'open', 'requested', 'initiated', 'instructed', 'verify', 'aborted',
            'timeout', 'pending', 'received', 'refunded', 'loss', 'error'
        );

        if (!in_array($struct->paymentStatus, $allowedStates)) {
            throw new \Shopware\Connect\Exception\VerificationFailedException(
                sprintf(
                    'Invalid order state given: %s. Expected one of: %s',
                    $struct->paymentStatus,
                    implode(', ', $allowedStates)
                )
            );
        }
    }

}