<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * @version 1.0.0snapshot201303151129
 */

namespace Bepado\SDK\Struct;

use Bepado\SDK\Struct;

/**
 * Struct class representing a multi-shop reservation
 *
 * @version 1.0.0snapshot201303151129
 * @api
 */
class Reservation extends Struct
{
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
