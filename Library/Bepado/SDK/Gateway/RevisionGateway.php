<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * @version $Revision$
 */

namespace Bepado\SDK\Gateway;

/**
 * Gateaway interface to maintain revision data
 *
 * @version $Revision$
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
