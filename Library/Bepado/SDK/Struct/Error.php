<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * @version 1.1.133
 */

namespace Bepado\SDK\Struct;

use Bepado\SDK\Struct;

/**
 * Struct class representing an error message
 *
 * @version 1.1.133
 * @api
 */
class Error extends Struct
{
    /**
     * @var string
     */
    public $message;

    /**
     * @var string
     */
    public $debugText;
}
