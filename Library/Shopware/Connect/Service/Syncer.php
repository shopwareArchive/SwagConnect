<?php
/**
 * This file is part of the Shopware Connect SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Shopware\Connect\Service;

use Shopware\Connect\Gateway;
use Shopware\Connect\Gateway\ChangeGateway;
use Shopware\Connect\Gateway\ProductGateway;
use Shopware\Connect\ProductFromShop;
use Shopware\Connect\ProductHasher;
use Shopware\Connect\RevisionProvider;

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
     * @var \Shopware\Connect\Gateway\ProductGateway
     */
    protected $products;

    /**
     * Gateway to changes feed
     *
     * @var \Shopware\Connect\Gateway\ChangeGateway
     */
    protected $changes;

    /**
     * Product from shop
     *
     * @var \Shopware\Connect\ProductFromShop
     */
    protected $fromShop;

    /**
     * Revision provider
     *
     * @var \Shopware\Connect\RevisionProvider
     */
    protected $revisions;

    /**
     * Product hasher
     *
     * @var \Shopware\Connect\ProductHasher
     */
    protected $hasher;

    /**
     * Construct from gateway
     *
     * @param \Shopware\Connect\Gateway\ProductGateway $products
     * @param \Shopware\Connect\Gateway\ChangeGateway $changes
     * @param \Shopware\Connect\ProductFromShop $fromShop
     * @param \Shopware\Connect\RevisionProvider $revisions
     * @param \Shopware\Connect\ProductHasher $hasher
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
