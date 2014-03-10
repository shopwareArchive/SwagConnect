<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * @version 1.1.142
 */

namespace Bepado\SDK\HttpClient;

use Bepado\SDK\Struct;

/**
 * Struct class representing a message
 *
 * @version 1.1.142
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
