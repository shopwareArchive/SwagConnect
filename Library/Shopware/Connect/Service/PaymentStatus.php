<?php
/**
 * This file is part of the Shopware Connect SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Shopware\Connect\Service;

use Shopware\Connect\Gateway\ChangeGateway;
use Shopware\Connect\ProductFromShop;
use Shopware\Connect\Gateway\ShopConfiguration;

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
     * @var \Shopware\Connect\Gateway\ChangeGateway
     */
    protected $changes;

    /**
     * @var \Shopware\Connect\ProductFromShop
     */
    protected $fromShop;

    /**
     * @var \Shopware\Connect\ShopConfiguration
     */
    protected $shopConfiguration;

    public function __construct(ChangeGateway $changes, ProductFromShop $fromShop, ShopConfiguration $shopConfiguration)
    {
        $this->changes = $changes;
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
     * @param array<\Shopware\Connect\Struct\PaymentStatus> $statuses
     */
    public function replicate(array $statuses)
    {
        foreach ($statuses as $status) {
            $revision = $status->revision;
            $this->fromShop->updatePaymentStatus($status);
            $this->shopConfiguration->setConfig(self::PAYMENT_REVISION, $revision);
        }
    }

    /**
     * @param $since
     * @param $limit
     * @return \Shopware\Connect\Struct\Change[]
     */
    public function getChanges($since, $limit)
    {
        $changes = $this->changes->getNextPaymentStatusChanges($since, $limit);
        $this->changes->cleanChangesUntil($since);

        return $changes;
    }

    /**
     * Update payment status in provider shop
     *
     * @param array<\Shopware\Connect\Struct\PaymentStatus> $changes
     */
    public function productPaymentsFromShop(array $changes)
    {
        $this->replicate($changes);
    }
}
