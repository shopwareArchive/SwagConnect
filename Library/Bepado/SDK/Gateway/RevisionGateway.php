<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * @version 1.1.133
 */

namespace Bepado\SDK\Gateway;

/**
 * Gateaway interface to maintain revision data
 *
 * @version 1.1.133
 * @api
 */
interface RevisionGateway
{
    /**
     * Get last processed import revision
     *
     * @return string
     */
    public function getLastRevision();

    /**
     * Store last processed import revision
     *
     * @param string $revision
     * @return void
     */
    public function storeLastRevision($revision);
}
