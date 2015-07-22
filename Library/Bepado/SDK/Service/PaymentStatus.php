<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Bepado\SDK\Service;

use Bepado\SDK\ProductFromShop;
use Bepado\SDK\Gateway\ShopConfiguration;

/**
 * Service to maintain payments
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */
class PaymentStatus
{
    /**
     * @var string
     */
    const PAYMENT_REVISION = '_payment_revision_';

    /**
     * @var \Bepado\SDK\ProductFromShop
     */
    protected $fromShop;

    /**
     * @var \Bepado\SDK\ShopConfiguration
     */
    protected $shopConfiguration;

    public function __construct(ProductFromShop $fromShop, ShopConfiguration $shopConfiguration)
    {
        $this->fromShop = $fromShop;
        $this->shopConfiguration = $shopConfiguration;
    }

    /**
     * Get last revision
     *
     * @return string
     */
    public function lastRevision()
    {
        return $this->shopConfiguration->getConfig(self::PAYMENT_REVISION);
    }

    /**
     * Update payment status
     *
     * @param array<\Bepado\SDK\Struct\PaymentStatus> $statuses
     */
    public function replicate(array $statuses)
    {
        foreach ($statuses as $status) {
            $revision = $status->revision;
            $this->fromShop->updatePaymentStatus($status);
            $this->shopConfiguration->setConfig(self::PAYMENT_REVISION, $revision);
        }
    }
}
