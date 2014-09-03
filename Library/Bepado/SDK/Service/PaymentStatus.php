<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Bepado\SDK\Service;

use Bepado\SDK\ProductPayments;

/**
 * Service to maintain payments
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */
class PaymentStatus
{
    /**
     * @var \Bepado\SDK\ProductPayments
     */
    protected $productPayments;

    public function __construct(ProductPayments $productPayments)
    {
        $this->productPayments = $productPayments;
    }

    /**
     * Get last revision
     *
     * @return string
     */
    public function lastRevision()
    {
        return $this->productPayments->lastRevision();
    }

    /**
     * Update payment status
     *
     * @param $localOrderId
     * @param \Bepado\SDK\Struct\PaymentStatus $status
     */
    public function updatePaymentStatus($localOrderId, \Bepado\SDK\Struct\PaymentStatus $status)
    {
        $this->productPayments->updatePaymentStatus($localOrderId, $status);
    }

} 