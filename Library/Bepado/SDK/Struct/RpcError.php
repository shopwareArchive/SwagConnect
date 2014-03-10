<?php

namespace Bepado\SDK\Struct;

use Bepado\SDK\Struct;

class RpcError extends Struct
{
    /**
     * @var string
     */
    public $message;

    /**
     * @var int
     */
    public $code;
}

