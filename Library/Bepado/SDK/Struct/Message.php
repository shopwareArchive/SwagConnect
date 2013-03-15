<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * @version 1.0.0snapshot201303151129
 */

namespace Bepado\SDK\Struct;

use Bepado\SDK\Struct;

/**
 * Struct class representing a message
 *
 * @version 1.0.0snapshot201303151129
 * @api
 */
class Message extends Struct
{
    /**
     * @var string
     */
    public $message;

    /**
     * Message variables
     *
     * @var array
     */
    public $values = array();
}
