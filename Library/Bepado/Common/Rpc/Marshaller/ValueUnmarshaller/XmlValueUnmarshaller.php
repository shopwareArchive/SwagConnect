<?php
/**
 * This file is part of the Bepado Common Component.
 *
 * @version 1.0.0snapshot201303151129
 */

namespace Bepado\Common\Rpc\Marshaller\ValueUnmarshaller;

use Bepado\Common\Struct;
use Bepado\Common\Rpc\Marshaller\ValueUnmarshaller;
use Bepado\Common\Rpc\Marshaller\Converter;

class XmlValueUnmarshaller implements ValueUnmarshaller
{
    /**
     * @var \Bepado\Common\Rpc\Marshaller\Converter
     */
    private $converter;

    /**
     * @param \Bepado\Common\Rpc\Marshaller\Converter $converter
     */
    public function __construct(Converter $converter)
    {
        $this->converter = $converter;
    }

    /**
     * @param \DOMElement $element
     * @return mixed
     */
    public function unmarshal(\DOMElement $element)
    {
        return $this->unmarshalValue($element);
    }

    /**
     * @param \DOMElement $element
     * @return mixed
     */
    private function unmarshalValue(\DOMElement $element)
    {
        switch ($element->localName) {
            case 'integer':
                return (int) $element->textContent;
            case 'float':
                return (float) $element->textContent;
            case 'string':
                return (string) $element->textContent;
            case 'boolean':
                return 'true' === $element->textContent;
            case 'null':
                return null;
            case 'array':
                $values = array();
                foreach ($element->childNodes as $child) {
                    $values[] = $this->unmarshalValue($child);
                }
                return $values;
            default:
                return $this->unmarshalObject($element);
        }
    }

    /**
     * @param \DOMElement $element
     * @return \Bepado\SDK\Struct|\Bepado\Common\Struct
     * @throws \OutOfBoundsException if target class is not registered for unmarshaling
     */
    private function unmarshalObject(\DOMElement $element)
    {
        $marshalledClass = $element->getAttribute("struct");
        $marshalledObject = new $marshalledClass();

        foreach ($element->childNodes as $child) {
            $marshalledObject->{$child->localName} = $this->unmarshalValue($child->firstChild);
        }

        return $this->converter->convertObject(
            $marshalledObject
        );
    }
}
