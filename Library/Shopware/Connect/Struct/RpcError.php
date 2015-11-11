<?php

namespace Shopware\Connect\Struct;

use Shopware\Connect\Struct;

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

