<?php
/**
 * This file is part of the Shopware Connect SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Shopware\Connect\Rpc\Marshaller\Converter;

use Shopware\Connect\Rpc\Marshaller\Converter;

class ChainingConverter extends Converter
{
    /**
     * @param \Shopware\Connect\Rpc\Marshaller\Converter[] $innerConverters
     */
    public function __construct(array $innerConverters = array())
    {
        foreach ($innerConverters as $innerConverter) {
            $this->addConverter($innerConverter);
        }
    }

    /**
     * @param \Shopware\Connect\Rpc\Marshaller\Converter $innerConverter
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
