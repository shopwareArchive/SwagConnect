<?php
/**
 * This file is part of the Bepado Common Component.
 *
 * @version 1.1.142
 */

namespace Bepado\SDK\Rpc\Marshaller;

use Bepado\SDK\Rpc\Marshaller\Converter;
use Bepado\SDK\Rpc\Marshaller\Converter\NoopConverter;

abstract class CallUnmarshaller
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
     * @param string $data
     * @return \Bepado\SDK\Struct\RpcCall
     */
    abstract public function unmarshal($data);
}
