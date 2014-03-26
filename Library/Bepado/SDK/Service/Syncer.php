<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Bepado\SDK\Service;

use Bepado\SDK\Gateway;
use Bepado\SDK\Gateway\ChangeGateway;
use Bepado\SDK\Gateway\ProductGateway;
use Bepado\SDK\ProductFromShop;
use Bepado\SDK\ProductHasher;
use Bepado\SDK\RevisionProvider;

/**
 * Service to sync product database with changes feed
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */
class Syncer
{
    /**
     * Gateway to products
     *
     * @var \Bepado\SDK\Gateway\ProductGateway
     */
    protected $products;

    /**
     * Gateway to changes feed
     *
     * @var \Bepado\SDK\Gateway\ChangeGateway
     */
    protected $changes;

    /**
     * Product from shop
     *
     * @var \Bepado\SDK\ProductFromShop
     */
    protected $fromShop;

    /**
     * Revision provider
     *
     * @var \Bepado\SDK\RevisionProvider
     */
    protected $revisions;

    /**
     * Product hasher
     *
     * @var \Bepado\SDK\ProductHasher
     */
    protected $hasher;

    /**
     * Construct from gateway
     *
     * @param \Bepado\SDK\Gateway\ProductGateway $products
     * @param \Bepado\SDK\Gateway\ChangeGateway $changes
     * @param \Bepado\SDK\ProductFromShop $fromShop
     * @param \Bepado\SDK\RevisionProvider $revisions
     * @param \Bepado\SDK\ProductHasher $hasher
     */
    public function __construct(
        ProductGateway $products,
        ChangeGateway $changes,
        ProductFromShop $fromShop,
        RevisionProvider $revisions,
        ProductHasher $hasher
    ) {
        $this->products = $products;
        $this->changes = $changes;
        $this->fromShop = $fromShop;
        $this->revisions = $revisions;
        $this->hasher = $hasher;
    }

    /**
     * Sync changes feed with internal database
     *
     * @return void
     */
    public function recreateChangesFeed()
    {
        $shopProducts = $this->fromShop->getExportedProductIDs();
        $knownProducts = $this->products->getAllProductIDs();

        if ($deletes = array_diff($knownProducts, $shopProducts)) {
            foreach ($deletes as $productId) {
                $this->changes->recordDelete($productId, $this->revisions->next());
            }
        }

        $myShopId = $this->products->getShopId();

        if ($inserts = array_diff($shopProducts, $knownProducts)) {
            foreach ($this->fromShop->getProducts($inserts) as $product) {
                $product->shopId = $myShopId;

                $this->changes->recordInsert(
                    $product->sourceId,
                    $this->hasher->hash($product),
                    $this->revisions->next(),
                    $product
                );
            }
        }

        if ($toCheck = array_intersect($shopProducts, $knownProducts)) {
            foreach ($this->fromShop->getProducts($toCheck) as $product) {
                $product->shopId = $myShopId;

                if ($this->products->hasChanged(
                    $product->sourceId,
                    $this->hasher->hash($product)
                )) {
                    $this->changes->recordUpdate(
                        $product->sourceId,
                        $this->hasher->hash($product),
                        $this->revisions->next(),
                        $product
                    );
                }
            }
        }
    }
}
