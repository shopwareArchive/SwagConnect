<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * @version 1.1.142
 */

namespace Bepado\SDK\Struct;

use Bepado\SDK\Struct;

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
     * @var \Bepado\SDK\Struct\Message[] $messages
     */
    public $messages = array();

    /**
     * Optional tracking data
     *
     * @var \Bepado\SDK\Struct\Tracking
     */
    public $tracking;
}
