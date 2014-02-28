<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * @version 1.1.133
 */

namespace Bepado\SDK\Gateway;

use Bepado\SDK\Struct\Product;

/**
 * Gateway interface to maintain changes feed
 *
 * @version 1.1.133
 * @api
 */
interface ChangeGateway
{
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
     * @return Struct\Changes[]
     */
    public function getNextChanges($offset, $limit);

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
}
