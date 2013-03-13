<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * @version 1.0.0snapshot201303061109
 */

namespace Bepado\SDK\Service;

use Bepado\SDK\Gateway\ChangeGateway;
use Bepado\Common\Struct;

/**
 * Service to receive current shop metrics
 *
 * @version 1.0.0snapshot201303061109
 */
class Metric
{
    /**
     * Gateway to changes feed
     *
     * @var \Bepado\SDK\Gateway\ChangeGateway
     */
    protected $changes;

    /**
     * Construct from gateway
     *
     * @param \Bepado\SDK\Gateway\ChangeGateway $changes
     */
    public function __construct(
        ChangeGateway $changes
    ) {
        $this->changes = $changes;
    }

    /**
     * Export current change state to Bepado
     *
     * @param string $revision
     * @param int $productCount
     * @return \Bepado\Common\Struct\Metric[]
     */
    public function fromShop($revision, $productCount)
    {
        return array(
            new Struct\Metric\Count(
                array(
                    'name' => 'sdk.changes_backlog',
                    'count' => $this->changes->getUnprocessedChangesCount($revision, $productCount),
                )
            ),
        );
    }
}
