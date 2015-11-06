<?php
/**
 * This file is part of the Shopware Connect SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Shopware\Connect\Struct;

use Shopware\Connect\Struct;

class OrderStatus extends Struct
{
    const STATE_OPEN = 'open';
    const STATE_IN_PROCESS = 'in_process';
    const STATE_DELIVERED = 'delivered';
    const STATE_CANCELED = 'canceled';
    const STATE_ERROR = 'error';

    /**
     * Order ID
     *
     * @var string
     */
    public $id;

    /**
     * Status of the Order
     *
     * The status is one of:
     *
     * - open
     * - in_process
     * - delivered
     * - canceled
     * - error
     *
     * See the STATE_ constants.
     *
     * @var string
     */
    public $status;

    /**
     * @var \Shopware\Connect\Struct\Message[] $messages
     */
    public $messages = array();

    /**
     * Optional tracking data
     *
     * @var \Shopware\Connect\Struct\Tracking
     */
    public $tracking;
}
