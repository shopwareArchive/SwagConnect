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
