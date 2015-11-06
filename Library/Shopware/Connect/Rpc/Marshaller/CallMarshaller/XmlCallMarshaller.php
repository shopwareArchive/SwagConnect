<?php
/**
 * This file is part of the Shopware Connect SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Shopware\Connect\Rpc\Marshaller\CallMarshaller;

use Shopware\Connect\Rpc\Marshaller\CallMarshaller;
use Shopware\Connect\Struct;
use Shopware\Connect\Rpc\Marshaller\ValueMarshaller;
use Shopware\Connect\Rpc\Marshaller\Converter;
use Shopware\Connect\Rpc\Marshaller\Converter\NoopConverter;
use Shopware\Connect\XmlHelper;

class XmlCallMarshaller extends CallMarshaller
{
    /**
     * Namespace prefix to schema mapping
     *
     * The namespace urn of the form <tt>urn:bepado:api:${prefix}</tt> will be
     * automatically generated from this
     *
     * @var array
     */
    private $namespaces = array(
        "rpc" => "http://api.bepado.com/schema/rpc.xsd",
        "array" => "http://api.bepado.com/schema/array.xsd",
        "float" => "http://api.bepado.com/schema/float.xsd",
        "integer" => "http://api.bepado.com/schema/integer.xsd",
        "boolean" => "http://api.bepado.com/schema/boolean.xsd",
        "string" => "http://api.bepado.com/schema/string.xsd",
        "null" => "http://api.bepado.com/schema/null.xsd",
    );

    /**
     * @var \Shopware\Connect\XmlHelper
     */
    private $helper;

    /**
     * @var \DOMDocument
     */
    private $document;

    /**
     * @var \Shopware\Connect\Rpc\Marshaller\ValueMarshaller\XmlValueMarshaller
     */
    private $valueMarshaller;

    /**
     * @param \Shopware\Connect\XmlHelper $helper
     * @param \Shopware\Connect\Rpc\Marshaller\Converter|null $converter
     */
    public function __construct(XmlHelper $helper, Converter $converter = null)
    {
        $this->converter = $converter ?: new NoopConverter();
        $this->helper = $helper;
    }

    /**
     * @param Struct\RpcCall $rpcCall
     * @return string
     */
    public function marshal(Struct\RpcCall $rpcCall)
    {
        $this->document = new \DOMDocument("1.0", "utf-8");
        $this->document->formatOutput = true;
        $this->valueMarshaller = new ValueMarshaller\XmlValueMarshaller(
            $this->helper,
            $this->converter,
            $this->document
        );

        $this->document->appendChild(
            $this->marshalRpcCall($rpcCall)
        );

        $this->updateNamespaceInformation(
            $this->document->documentElement
        );

        return $this->document->saveXML();
    }

    /**
     * @param \Shopware\Connect\Struct\RpcCall $rpcCall
     * @return string
     */
    private function marshalRpcCall(Struct\RpcCall $rpcCall)
    {
        $rpc = $this->document->createElement("rpc");
        $this->helper->updateDefaultNamespace($rpc, "urn:bepado:api:rpc");
        $this->document->appendChild($rpc);

        $service = $this->document->createElement("service");
        $service->appendChild(
            $this->document->createTextNode(
                $rpcCall->service
            )
        );
        $rpc->appendChild($service);

        $command = $this->document->createElement("command");
        $command->appendChild(
            $this->document->createTextNode(
                $rpcCall->command
            )
        );
        $rpc->appendChild($command);

        $arguments = $this->document->createElement("arguments");
        foreach ($rpcCall->arguments as $argument) {
            $arguments->appendChild(
                $this->valueMarshaller->marshal($argument)
            );
        }
        $rpc->appendChild($arguments);

        return $rpc;
    }

    /**
     * @param \DOMElement $element
     */
    private function updateNamespaceInformation(\DOMElement $element)
    {
        $schemaLocations = array();
        foreach ($this->namespaces as $prefix => $schema) {
            $urn = "urn:bepado:api:{$prefix}";
            $this->helper->updateRelativeNamespacePrefix(
                $element,
                $urn,
                $prefix
            );
            $schemaLocations[$urn] = $schema;
        }

        $this->helper->updateSchemaLocation(
            $element,
            $schemaLocations
        );
    }
}
