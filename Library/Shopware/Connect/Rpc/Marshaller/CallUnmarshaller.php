<?php
/**
 * This file is part of the Shopware Connect SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Shopware\Connect\Rpc\Marshaller;

use Shopware\Connect\Rpc\Marshaller\Converter;
use Shopware\Connect\Rpc\Marshaller\Converter\NoopConverter;

abstract class CallUnmarshaller
{
    /**
     * @var \Shopware\Connect\Rpc\Marshaller\Converter
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
     * @return \Shopware\Connect\Struct\RpcCall
     */
    abstract public function unmarshal($data);
}
