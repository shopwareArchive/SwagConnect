<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * @version 1.1.133
 */

namespace Bepado\SDK\Struct;

use Bepado\SDK\Struct;

/**
 * Struct class representing a multi-shop reservation
 *
 * @version 1.1.133
 * @api
 */
class Reservation extends Struct
{
    /**
     * Indicator if reservation failed or not
     *
     * @var bool
     */
    public $success = false;

    /**
     * Messages from shops, where the reservation failed.
     *
     * @var array
     */
    public $messages = array();

    /**
     * Orders per shop
     *
     * @var Struct\Order[]
     */
    public $orders = array();
}
