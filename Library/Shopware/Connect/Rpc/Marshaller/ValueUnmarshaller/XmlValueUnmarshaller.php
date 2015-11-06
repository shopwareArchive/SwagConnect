<?php
/**
 * This file is part of the Shopware Connect SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Shopware\Connect\Rpc\Marshaller\ValueUnmarshaller;

use Shopware\Connect\Struct;
use Shopware\Connect\Rpc\Marshaller\ValueUnmarshaller;
use Shopware\Connect\Rpc\Marshaller\Converter;

class XmlValueUnmarshaller implements ValueUnmarshaller
{
    /**
     * @var \Shopware\Connect\Rpc\Marshaller\Converter
     */
    private $converter;

    /**
     * @param \Shopware\Connect\Rpc\Marshaller\Converter $converter
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
     * @return \Shopware\Connect\Struct|\Shopware\Connect\Struct
     * @throws \OutOfBoundsException if target class is not registered for unmarshaling
     */
    private function unmarshalObject(\DOMElement $element)
    {
        $marshalledClass = $element->getAttribute("struct");

        if (!is_subclass_of($marshalledClass, 'Shopware\Connect\Struct') &&
            !is_subclass_of($marshalledClass, 'Bepado\Common\Struct')) {
            throw new \RuntimeException(sprintf("Cannot unmarshall non-Struct classes such as %s", $marshalledClass));
        }

        $marshalledObject = new $marshalledClass();

        foreach ($element->childNodes as $child) {
            // property_exists() does not work on virtual properties used for
            // BC. thus we just try to set the property and catch possible
            // exceptions.
            try {
                $marshalledObject->{$child->localName} = $this->unmarshalValue($child->firstChild);
            } catch (\OutOfRangeException $e) {
                // Ignore. We ignore unknown properties.
            }
        }

        return $this->converter->convertObject(
            $marshalledObject
        );
    }
}
