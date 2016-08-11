<?php
/**
 * This file is part of the Shopware Connect SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Shopware\Connect\Gateway;

use Shopware\Connect\Struct\PaymentStatus;
use Shopware\Connect\Struct\Product;

/**
 * Gateway interface to maintain changes feed
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 * @api
 */
interface ChangeGateway
{
    const TYPE_PRODUCT = 'product';
    const TYPE_PAYMENT = 'payment';
    const PRODUCT_INSERT = 'insert';
    const PRODUCT_UPDATE = 'update';
    const PRODUCT_DELETE = 'delete';
    const PRODUCT_STOCK = 'stock';
    const STREAM_ASSIGNMENT = 's-assign';
    const MAIN_VARIANT = 'mvariant';
    const PAYMENT_UPDATE = 'pmnt-u';

    /**
     * Get next changes
     *
     * The offset specified the revision to start from
     *
     * May remove all pending changes, which are prior to the last requested
     * revision.
     *
     * @param string $offset
     * @param int $limit
     * @return \Shopware\Connect\Struct\Change[]
     */
    public function getNextChanges($offset, $limit);

    /**
     * @param $offset
     * @param $limit
     * @return mixed
     */
    public function getNextPaymentStatusChanges($offset, $limit);

    /**
     * Remove changes from the storage until the given offset.
     *
     * @param string $offset
     * @return void
     */
    public function cleanChangesUntil($offset);

    /**
     * Get unprocessed changes count
     *
     * The offset specified the revision to start from
     *
     * Important: This value may be an estimation only as its only used for
     * Metrics calculation.  Don't rely on this value to be correct.
     *
     * @param string $offset
     * @param int $limit
     * @return int
     */
    public function getUnprocessedChangesCount($offset, $limit);

    /**
     * Record product insert
     *
     * @param string $id
     * @param string $hash
     * @param string $revision
     * @param Product $product
     * @return void
     */
    public function recordInsert($id, $hash, $revision, Product $product);

    /**
     * Record product update
     *
     * @param string $id
     * @param string $hash
     * @param string $revision
     * @param Product $product
     * @return void
     */
    public function recordUpdate($id, $hash, $revision, Product $product);

    /**
     * Record product delete
     *
     * @param string $id
     * @param string $revision
     * @return void
     */
    public function recordDelete($id, $revision);

    /**
     * Record product availability update
     *
     * @param string $id
     * @param string $hash
     * @param string $revision
     * @param Product $product
     * @return void
     */
    public function recordAvailabilityUpdate($id, $hash, $revision, Product $product);

    /**
     * Record stream assignment
     *
     * @param string $productId
     * @param string $revision
     * @param array $supplierStreams
     */
    public function recordStreamAssignment($productId, $revision, array $supplierStreams);

    /**
     * Makes variant to be main
     *
     * @param $productId
     * @param $revision
     * @param $groupId
     */
    public function makeMainVariant($productId, $revision, $groupId);

    /**
     * Update payment status
     *
     * @param $revision
     * @param PaymentStatus $paymentStatus
     * @return void
     */
    public function updatePaymentStatus($revision, PaymentStatus $paymentStatus);
}
