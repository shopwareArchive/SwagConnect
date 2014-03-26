<?php
/**
 * This file is part of the Bepado Common Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Bepado\SDK\Rpc\Marshaller\Converter;

use Bepado\SDK\Rpc\Marshaller\Converter;

class ChainingConverter extends Converter
{
    /**
     * @param \Bepado\SDK\Rpc\Marshaller\Converter[] $innerConverters
     */
    public function __construct(array $innerConverters = array())
    {
        foreach ($innerConverters as $innerConverter) {
            $this->addConverter($innerConverter);
        }
    }

    /**
     * @param \Bepado\SDK\Rpc\Marshaller\Converter $innerConverter
     */
    public function addConverter(Converter $innerConverter)
    {
        $this->innerConverters[] = $innerConverter;
    }

    /**
     * @param mixed $object
     * @return mixed
     */
    public function convertObject($object)
    {
        foreach ($this->innerConverters as $innerConverter) {
            $object = $innerConverter->convertObject($object);
        }
        return $object;
    }
}
