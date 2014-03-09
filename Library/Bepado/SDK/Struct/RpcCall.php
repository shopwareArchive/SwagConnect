<?php
namespace Bepado\SDK\Struct;

use \Bepado\SDK\Struct;

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
