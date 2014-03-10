<?php

namespace Bepado\SDK\Struct;

use Bepado\SDK\Struct;

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
