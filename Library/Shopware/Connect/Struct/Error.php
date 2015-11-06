<?php

namespace Shopware\Connect\Struct;

use Shopware\Connect\Struct;

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
