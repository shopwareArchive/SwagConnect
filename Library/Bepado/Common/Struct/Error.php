<?php

namespace Bepado\Common\Struct;

use \Bepado\Common\Struct;

class Error extends Struct
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
