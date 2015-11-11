<?php
/**
 * This file is part of the Shopware Connect SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Shopware\Connect\Service;

use Shopware\Connect\Gateway;
use Shopware\Connect\ProductToShop;
use Shopware\Connect\ProductFromShop;
use Shopware\Connect\Struct\Change;
use Shopware\Connect\Struct;
use Shopware\Connect\Gateway\ChangeGateway;
use Shopware\Connect\Gateway\RevisionGateway;
use Shopware\Connect\Gateway\ShopConfiguration;

/**
 * Product service
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */
class ProductService
{
    /**
     * Gateway to changes feed
     *
     * @var \Shopware\Connect\Gateway\ChangeGateway
     */
    protected $changes;

    /**
     * Gateway to revision storage
     *
     * @var \Shopware\Connect\Gateway\RevisionGateway
     */
    protected $revision;

    /**
     * Gateway for shop configurations
     *
     * @var \Shopware\Connect\Gateway\ShopConfiguration
     */
    protected $configurationGateway;

    /**
     * Product importer
     *
     * @var \Shopware\Connect\ProductToShop
     */
    protected $toShop;

    /**
     * @var \Shopware\Connect\ProductFromShop
     */
    protected $fromShop;

    /**
     * Construct from gateway
     *
     * @param ChangeGateway $changes
     * @param RevisionGateway $revision
     * @param ShopConfiguration $configurationGateway
     * @param ProductToShop $toShop
     * @param ProductFromShop $fromShop
     * @return void
     */
    public function __construct(
        ChangeGateway $changes,
        RevisionGateway $revision,
        ShopConfiguration $configurationGateway,
        ProductToShop $toShop,
        ProductFromShop $fromShop
    ) {
        $this->changes = $changes;
        $this->revision = $revision;
        $this->configurationGateway = $configurationGateway;
        $this->toShop = $toShop;
        $this->fromShop = $fromShop;
    }

    /**
     * @deprecated Use the getChanges() method directly
     */
    public function fromShop($revision, $productCount)
    {
        return $this->getChanges($revision, $productCount);
    }

    /**
     * Export current change state to Shopware Connect
     *
     * @param string $since
     * @param int $limit
     * @return \Shopware\Connect\Struct\Change[]
     */
    public function getChanges($since, $limit)
    {
        $changes = $this->changes->getNextChanges($since, $limit);

        $this->changes->cleanChangesUntil($since);

        return $changes;
    }

    /**
     * Take a look at products from given revision.
     *
     * @param string $revision
     * @param int $productCount
     * @return \Shopware\Connect\Struct\Change[]
     */
    public function peakFromShop($revision, $productCount)
    {
        return $this->changes->getNextChanges($revision, $productCount);
    }

    /**
     * Return all products matching the given ids.
     *
     * @param array<string> $ids
     * @return \Shopware\Connect\Struct\Product[]
     */
    public function peakProducts(array $ids)
    {
        if (count($ids) > 50) {
            throw new \InvalidArgumentException("Too many products requested.");
        }

        return $this->fromShop->getProducts($ids);
    }

    /**
     * @deprecated Just use the replicate() method directly
     */
    public function toShop(array $changes)
    {
        return $this->replicate($changes);
    }

    /**
     * Import changes into shop
     *
     * @param \Shopware\Connect\Struct\Change[] $changes
     * @return string
     */
    public function replicate(array $changes)
    {
        $this->toShop->startTransaction();
        foreach ($changes as $change) {
            switch (true) {
                case ($change instanceof Change\ToShop\InsertOrUpdate):
                    $this->toShop->insertOrUpdate($change->product);
                    break;

                case ($change instanceof Change\ToShop\Availability):
                    $this->toShop->changeAvailability($change->shopId, $change->sourceId, (int)$change->availability);
                    break;

                case ($change instanceof Change\ToShop\Update):
                    $this->toShop->update($change->shopId, $change->sourceId, $change->product);
                    break;

                case ($change instanceof Change\ToShop\Delete):
                    $this->toShop->delete($change->shopId, $change->sourceId);
                    break;

                default:
                    throw new \RuntimeException("Invalid change operation: $change");
            }
        }

        $this->revision->storeLastRevision($change->revision);

        $this->toShop->commit();
        return $change->revision;
    }

    /**
     * @deprecated Just use the lastRevision() method directly
     */
    public function getLastRevision()
    {
        return $this->lastRevision();
    }

    /**
     * Get last processed revision in shop
     *
     * @return string
     */
    public function lastRevision()
    {
        return $this->revision->getLastRevision();
    }
}
