<?php
/**
 * This file is part of the Bepado Common Component.
 *
 * @version 1.0.0snapshot201303061109
 */

namespace Bepado\Common\Rpc\Marshaller\ValueMarshaller;

use Bepado\Common\Struct;
use Bepado\Common\Rpc\Marshaller\ValueMarshaller;
use Bepado\Common\XmlHelper;
use Bepado\Common\Rpc\Marshaller\Converter;

class XmlValueMarshaller implements ValueMarshaller
{
    /**
     * Mapping of urn => schema locations used during marshaling
     *
     * @var array
     */
    private $objectUrns = array();

    /**
     * @var \DOMDocument
     */
    private $document;

    /**
     * @var \Bepado\Common\XmlHelper
     */
    private $helper;

    /**
     * @var \Bepado\Common\Rpc\Marshaller\Converter
     */
    private $converter;

    /**
     * @param \Bepado\Common\XmlHelper $helper
     * @param \Bepado\Common\Rpc\Marshaller\Converter $converter
     * @param \DOMDocument $document
     */
    public function __construct(XmlHelper $helper, Converter $converter, \DOMDocument $document)
    {
        $this->helper = $helper;
        $this->converter = $converter;
        $this->document = $document;
    }

    /**
     * @param mixed $value
     * @return \DOMDocumentFragment
     * @throws \OutOfRangeException
     */
    public function marshal($value)
    {
        $this->objectUrns = array();
        $root = $this->marshalValue($value);

        // Add a schemaLocation entry for each used schema. This eases validation.
        if (count($this->objectUrns) > 0) {
            $this->helper->updateSchemaLocation(
                $root,
                $this->objectUrns
            );
        }

        $fragment = $this->document->createDocumentFragment();
        $fragment->appendChild($root);
        return $fragment;
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return \DOMElement
     */
    private function marshalProperty($name, $value)
    {
        $element = $this->document->createElement($name);
        $element->appendChild($this->marshalValue($value));
        return $element;
    }

    /**
     * @param mixed $value
     * @return \DOMElement
     */
    private function marshalValue($value)
    {
        if (is_object($value)) {
            return $this->marshalObject($value);
        } elseif (is_array($value)) {
            return $this->marshalArray($value);
        }
        return $this->marshalScalar($value);
    }

    /**
     * @param \Bepado\SDK\Struct|\Bepado\Common\Struct $object
     * @return \DOMElement
     * @throws \OutOfRangeException if no translation for the given object exists
     */
    private function marshalObject($object)
    {
        $targetObject = $this->converter->convertObject($object);

        // The targetname is the last part of the fqdn classname
        $targetClass = get_class($targetObject);
        $targetName = strtolower(ltrim(substr($targetClass, strrpos($targetClass, '\\')), '\\'));

        $elementNamespace = "urn:bepado:api:{$targetName}";

        $element = $this->document->createElement($targetName);
        $element->setAttribute("struct", $targetClass);
        $this->helper->updateDefaultNamespace($element, $elementNamespace);
        $this->objectUrns[$elementNamespace] = "http://api.bepado.com/schemas/{$targetName}.xsd";

        $properties = get_object_vars($targetObject);
        foreach ($properties as $property => $value) {
            $element->appendChild(
                $this->marshalProperty($property, $value)
            );
        }

        return $element;
    }

    /**
     * @param array $data
     * @return \DOMElement
     */
    private function marshalArray(array $data)
    {
        $node = $this->document->createElement("array:array");

        foreach ($data as $value) {
            $node->appendChild($this->marshalValue($value));
        }

        return $node;
    }

    /**
     * @param int|bool|float|string|null $value
     * @return \DOMElement
     * @throws \ErrorException
     */
    private function marshalScalar($value)
    {
        switch (true) {
            case is_integer($value):
                $name = "integer";
                break;
            case is_float($value):
                $name = "float";
                break;
            case is_bool($value):
                $name = "boolean";
                $value = ($value === true) ? "true" : "false";
                break;
            case is_string($value):
                $name = "string";
                break;
            case is_null($value):
                $name = "null";
                break;
            default:
                throw new \ErrorException("Unsupported scalar type" . gettype($value) . ".");
        }
        $node = $this->document->createElement("{$name}:{$name}");

        // NULL is a special case, it does not have any value
        if ($name === "null") {
            return $node;
        }

        // Check if the content does contain anything, which would need CDATA
        // encapsulation
        if (htmlspecialchars($value, ENT_NOQUOTES, 'UTF-8') == $value) {
            $node->appendChild($this->document->createTextNode($value));
        } else {
            $node->appendChild($this->document->createCDATASection($value));
        }

        return $node;
    }
}
