<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Bepado\SDK\Struct;

use Bepado\SDK\Struct;

class PaymentStatus extends Struct
{
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
} 