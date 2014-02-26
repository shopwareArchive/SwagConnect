<?php
/**
 * This file is part of the Bepado Common Component.
 *
 * @version 1.0.129
 */

namespace Bepado\Common\Rpc\Marshaller\Converter;

use Bepado\Common\Rpc\Marshaller\Converter;

class ChainingConverter extends Converter
{
    /**
     * @param \Bepado\Common\Rpc\Marshaller\Converter[] $innerConverters
     */
    public function __construct(array $innerConverters = array())
    {
        foreach ($innerConverters as $innerConverter) {
            $this->addConverter($innerConverter);
        }
    }

    /**
     * @param \Bepado\Common\Rpc\Marshaller\Converter $innerConverter
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
