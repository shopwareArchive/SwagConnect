<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * @version $Revision$
 */

namespace Bepado\SDK\Service;

use Bepado\SDK\Gateway;
use Bepado\SDK\ProductToShop;
use Bepado\SDK\Struct\Change;
use Bepado\SDK\Struct;
use Bepado\SDK\Gateway\ChangeGateway;
use Bepado\SDK\Gateway\RevisionGateway;
use Bepado\SDK\Gateway\ShopConfiguration;

/**
 * Product service
 *
 * @version $Revision$
 */
class ProductService
{
    /**
     * Gateway to changes feed
     *
     * @var \Bepado\SDK\Gateway\ChangeGateway
     */
    protected $changes;

    /**
     * Gateway to revision storage
     *
     * @var \Bepado\SDK\Gateway\RevisionGateway
     */
    protected $revision;

    /**
     * Gateway for shop configurations
     *
     * @var \Bepado\SDK\Gateway\ShopConfiguration
     */
    protected $configurationGateway;

    /**
     * Product importer
     *
     * @var \Bepado\SDK\ProductToShop
     */
    protected $toShop;

    /**
     * Construct from gateway
     *
     * @param ChangeGateway $changes
     * @param RevisionGateway $revision
     * @param ShopConfiguration $configurationGateway
     * @param ProductToShop $toShop
     * @return void
     */
    public function __construct(
        ChangeGateway $changes,
        RevisionGateway $revision,
        ShopConfiguration $configurationGateway,
        ProductToShop $toShop
    ) {
        $this->changes = $changes;
        $this->revision = $revision;
        $this->configurationGateway = $configurationGateway;
        $this->toShop = $toShop;
    }

    /**
     * Export current change state to Bepado
     *
     * @param string $revision
     * @param int $productCount
     * @return \Bepado\SDK\Struct\Change[]
     */
    public function fromShop($revision, $productCount)
    {
        return $this->changes->getNextChanges($revision, $productCount);
    }

    /**
     * Import changes into shop
     *
     * @param \Bepado\SDK\Struct\Change[] $changes
     * @return string
     */
    public function toShop(array $changes)
    {
        foreach ($changes as $change) {
            switch (true) {
                case $change instanceof Change\ToShop\InsertOrUpdate:
                    $this->toShop->insertOrUpdate($change->product);
                    break;
                case $change instanceof Change\ToShop\Delete:
                    $this->toShop->delete($change->shopId, $change->sourceId);
                    break;
                default:
                    throw new \RuntimeException("Invalid change operation: $change");
            }
        }

        $this->revision->storeLastRevision($change->revision);
        return $change->revision;
    }

    /**
     * Get last processed revision in shop
     *
     * @return string
     */
    public function getLastRevision()
    {
        return $this->revision->getLastRevision();
    }
}
