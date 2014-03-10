<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * @version 1.1.142
 */

namespace Bepado\SDK\Struct;

use Bepado\SDK\Struct;

/**
 * Struct class representing a message
 *
 * @version 1.1.142
 * @api
 */
class Message extends Struct
{
    /**
     * Message that might contain placeholders.
     *
     * Placeholders are identified by "%foo" (without quotes). They will be
     * replaced by values from $values.
     *
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
