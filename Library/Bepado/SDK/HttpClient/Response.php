<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * @version $Revision$
 */

namespace Bepado\SDK\HttpClient;

use Bepado\SDK\Struct;

/**
 * Struct class representing a message
 *
 * @version $Revision$
 * @api
 */
class Response extends Struct
{
    /**
     * @var int
     */
    public $status;

    /**
     * @var string
     */
    public $headers;

    /**
     * @var string
     */
    public $body;
}
