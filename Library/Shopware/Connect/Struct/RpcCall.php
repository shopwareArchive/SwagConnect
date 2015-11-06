<?php
namespace Shopware\Connect\Struct;

use \Shopware\Connect\Struct;

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
