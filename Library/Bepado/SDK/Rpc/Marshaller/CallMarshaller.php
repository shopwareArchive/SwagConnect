<?php
/**
 * This file is part of the Bepado Common Component.
 *
 * @version 1.1.141
 */

namespace Bepado\SDK\Rpc\Marshaller;

use Bepado\SDK\Struct;
use Bepado\SDK\Rpc\Marshaller\Converter;
use Bepado\SDK\Rpc\Marshaller\Converter\NoopConverter;

abstract class CallMarshaller
{
    /**
     * @var \Bepado\SDK\Rpc\Marshaller\Converter
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
