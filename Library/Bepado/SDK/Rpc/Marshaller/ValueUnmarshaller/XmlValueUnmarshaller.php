<?php
/**
 * This file is part of the Bepado Common Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Bepado\SDK\Rpc\Marshaller\ValueUnmarshaller;

use Bepado\SDK\Struct;
use Bepado\SDK\Rpc\Marshaller\ValueUnmarshaller;
use Bepado\SDK\Rpc\Marshaller\Converter;

class XmlValueUnmarshaller implements ValueUnmarshaller
{
    /**
     * @var \Bepado\SDK\Rpc\Marshaller\Converter
     */
    private $converter;

    /**
     * @param \Bepado\SDK\Rpc\Marshaller\Converter $converter
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
                    if ($child->hasAttribute('key')) {
                        $values[$child->getAttribute('key')] = $this->unmarshalValue($child);
                    } else {
                        $values[] = $this->unmarshalValue($child);
                    }
                }
                return $values;
            default:
                return $this->unmarshalObject($element);
        }
    }

    /**
     * @param \DOMElement $element
     * @return \Bepado\SDK\Struct|\Bepado\SDK\Struct
     * @throws \OutOfBoundsException if target class is not registered for unmarshaling
     */
    private function unmarshalObject(\DOMElement $element)
    {
        $marshalledClass = $element->getAttribute("struct");

        if (!is_subclass_of($marshalledClass, 'Bepado\SDK\Struct') &&
            !is_subclass_of($marshalledClass, 'Bepado\Common\Struct')) {
            throw new \RuntimeException("Cannot unmarshall non-Struct classes.");
        }

        $marshalledObject = new $marshalledClass();

        foreach ($element->childNodes as $child) {
            if (property_exists($marshalledObject, $child->localName)) {
                $marshalledObject->{$child->localName} = $this->unmarshalValue($child->firstChild);
            }
        }

        return $this->converter->convertObject(
            $marshalledObject
        );
    }
}
