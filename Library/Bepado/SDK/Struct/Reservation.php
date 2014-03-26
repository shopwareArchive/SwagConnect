<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Bepado\SDK\Struct;

use Bepado\SDK\Struct;

/**
 * Struct class representing a multi-shop reservation
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
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
