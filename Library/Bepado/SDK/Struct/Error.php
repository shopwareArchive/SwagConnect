<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * @version $Revision$
 */

namespace Bepado\SDK\Struct;

use Bepado\SDK\Struct;

/**
 * Struct class representing an error message
 *
 * @version $Revision$
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
