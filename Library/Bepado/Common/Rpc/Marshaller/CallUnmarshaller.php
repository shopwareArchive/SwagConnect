<?php
/**
 * This file is part of the Bepado Common Component.
 *
 * @version 1.0.0snapshot201303061109
 */

namespace Bepado\Common\Rpc\Marshaller;

use Bepado\Common\Rpc\Marshaller\Converter;
use Bepado\Common\Rpc\Marshaller\Converter\NoopConverter;

abstract class CallUnmarshaller
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
     * @param string $data
     * @return \Bepado\Common\Struct\RpcCall
     */
    abstract public function unmarshal($data);
}
