<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Bepado\SDK\HttpClient;

use Bepado\SDK\Struct;

/**
 * Struct class representing a message
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
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
