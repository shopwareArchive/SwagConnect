<?php
/**
 * This file is part of the Shopware Connect SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Shopware\Connect\Service;

use Shopware\Connect\Gateway\ChangeGateway;
use Shopware\Connect\Struct;

/**
 * Service to receive current shop metrics
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */
class Metric
{
    /**
     * Gateway to changes feed
     *
     * @var \Shopware\Connect\Gateway\ChangeGateway
     */
    protected $changes;

    /**
     * Construct from gateway
     *
     * @param \Shopware\Connect\Gateway\ChangeGateway $changes
     */
    public function __construct(
        ChangeGateway $changes
    ) {
        $this->changes = $changes;
    }

    /**
     * Export current change state to Shopware Connect
     *
     * @param string $revision
     * @param int $productCount
     * @return \Shopware\Connect\Struct\Metric[]
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
