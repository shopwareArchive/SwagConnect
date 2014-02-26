<?php
/**
 * This file is part of the Bepado Common Component.
 *
 * @version 1.0.129
 */

namespace Bepado\Common\Rpc\Marshaller;

use Bepado\Common\Struct;
use Bepado\Common\Rpc\Marshaller\Converter;
use Bepado\Common\Rpc\Marshaller\Converter\NoopConverter;

abstract class CallMarshaller
{
    /**
     * @var \Bepado\Common\Rpc\Marshaller\Converter
     */
    protected $converter;

    /**
     * @param Converter|null $converter
     */
    public function __construct(Converter $converter = null)
    {
        $this->converter = $converter ?: new NoopConverter();
    }

    /**
     * @param Struct\RpcCall $rpcCall
     * @return string
     */
    abstract public function marshal(Struct\RpcCall $rpcCall);
}
