<?php
namespace Bepado\Common\Struct;

use \Bepado\Common\Struct;

class RpcCall extends Struct
{
    /**
     * @var string
     */
    public $service;

    /**
     * @var string
     */
    public $command;

    /**
     * @var string[]
     */
    public $arguments = array();
}
