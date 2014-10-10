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
     * Update payment status
     *
     * @param PaymentStatus $status
     * @return mixed
     */
    public function updatePaymentStatus(PaymentStatus $status);
} 
