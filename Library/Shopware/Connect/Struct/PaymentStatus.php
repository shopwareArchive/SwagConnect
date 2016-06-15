<?php
/**
 * This file is part of the Shopware Connect SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Shopware\Connect\Struct;

use Shopware\Connect\Struct;

class PaymentStatus extends Struct
{
    const PAYMENT_OPEN      = 'open';
    const PAYMENT_REQUESTED = 'requested';
    const PAYMENT_INITIATED = 'initiated';
    const PAYMENT_INSTRUCTED = 'instructed';
    const PAYMENT_VERIFY    = 'verify';
    const PAYMENT_ABORTED   = 'aborted';
    const PAYMENT_TIMEOUT   = 'timeout';
    const PAYMENT_PENDING   = 'pending';
    const PAYMENT_RECEIVED  = 'received';
    const PAYMENT_REFUNDED  = 'refunded';
    const PAYMENT_LOSS      = 'loss';
    const PAYMENT_ERROR     = 'error';

    /**
     * @var string
     */
    public $localOrderId;

    /**
     * @var string
     */
    public $paymentStatus;

    /**
     * @var string
     */
    public $paymentProvider;

    /**
     * @var string
     */
    public $providerTransactionId;

    /**
     * @var string
     */
    public $revision;
}
