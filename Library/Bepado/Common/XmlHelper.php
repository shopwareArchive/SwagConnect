<?php
/**
 * This file is part of the Bepado Common component.
 *
 * @version 1.0.0snapshot201303151129
 */

namespace Bepado\Common;

/**
 * Utility class for xml handling.
 */
class XmlHelper
{
    /**
     * @param \DOMElement $element
     * @param string $urn
     *
     * @return void
     */
    public function updateDefaultNamespace(\DOMElement $element, $urn)
    {
        $element->setAttribute(
            "xmlns",
            $urn
        );
    }

    /**
     * @param \DOMElement $element
     * @param string $urn
     * @param string $prefix
     *
     * @return void
     */
    public function updateRelativeNamespacePrefix(\DOMElement $element, $urn, $prefix)
    {
        $element->setAttributeNS(
            "http://www.w3.org/2000/xmlns/",
            "xmlns:{$prefix}",
            $urn
        );
    }

    /**
     * @param \DOMElement $element
     * @param array $urnLocationMapping
     *
     * @return void
     */
    public function updateSchemaLocation(\DOMElement $element, array $urnLocationMapping)
    {
        $attributeValues = array();
        foreach ($urnLocationMapping as $urn => $location) {
            $attributeValues[] = $urn;
            $attributeValues[] = $location;
        }

        $element->setAttributeNS(
            "http://www.w3.org/2001/XMLSchema-instance",
            "xsi:schemaLocation",
            implode(" ", $attributeValues)
        );
    }
}
