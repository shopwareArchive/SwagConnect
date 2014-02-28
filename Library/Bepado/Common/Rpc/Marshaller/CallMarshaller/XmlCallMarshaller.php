<?php
/**
 * This file is part of the Bepado Common Component.
 *
 * @version 1.1.133
 */

namespace Bepado\Common\Rpc\Marshaller\CallMarshaller;

use Bepado\Common\Rpc\Marshaller\CallMarshaller;
use Bepado\Common\Struct;
use Bepado\Common\Rpc\Marshaller\ValueMarshaller;
use Bepado\Common\Rpc\Marshaller\Converter;
use Bepado\Common\Rpc\Marshaller\Converter\NoopConverter;
use Bepado\Common\XmlHelper;

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
     * @var \Bepado\Common\XmlHelper
     */
    private $helper;

    /**
     * @var \DOMDocument
     */
    private $document;

    /**
     * @var \Bepado\Common\Rpc\Marshaller\ValueMarshaller\XmlValueMarshaller
     */
    private $valueMarshaller;

    /**
     * @param \Bepado\Common\XmlHelper $helper
     * @param \Bepado\Common\Rpc\Marshaller\Converter|null $converter
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
     * @param \Bepado\Common\Struct\RpcCall $rpcCall
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
