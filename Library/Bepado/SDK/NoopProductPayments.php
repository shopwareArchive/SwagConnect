<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Bepado\SDK;

use Bepado\SDK\Struct\PaymentStatus;

/**
 * Noop Product Payments
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */
class NoopProductPayments implements ProductPayments
{
    /**
     * @return string
     */
    public function lastRevision()
    {
        return (string)microtime(true);
    }

    /**
     * @param int $localOrderId
     * @param PaymentStatus $status
     * @return mixed|void
     */
    public function updatePaymentStatus($localOrderId, PaymentStatus $status)
    {

    }
} 