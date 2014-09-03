<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Bepado\SDK;

use Bepado\SDK\Struct\PaymentStatus;

/**
 * Product Payments
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */
interface ProductPayments
{
    /**
     * Get last revision
     *
     * @return string
     */
    public function lastRevision();

    /**
     * Update payment status
     *
     * @param int $localOrderId
     * @param PaymentStatus $status
     * @return mixed
     */
    public function updatePaymentStatus($localOrderId, PaymentStatus $status);
} 