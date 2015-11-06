<?php
/**
 * This file is part of the Shopware Connect SDK component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Shopware\Connect\Rpc\ErrorHandler;

use Shopware\Connect\Rpc\ErrorHandler;

class XmlErrorHandler extends ErrorHandler
{
    public function renderResponse($message)
    {
        $message = htmlentities($message, ENT_COMPAT, 'UTF-8');

        $xml = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<rpc xmlns="urn:bepado:api:rpc" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:rpc="urn:bepado:api:rpc" xmlns:array="urn:bepado:api:array" xmlns:float="urn:bepado:api:float" xmlns:integer="urn:bepado:api:integer" xmlns:boolean="urn:bepado:api:boolean" xmlns:string="urn:bepado:api:string" xmlns:null="urn:bepado:api:null" xsi:schemaLocation="urn:bepado:api:rpc http://api.bepado.com/schema/rpc.xsd urn:bepado:api:array http://api.bepado.com/schema/array.xsd urn:bepado:api:float http://api.bepado.com/schema/float.xsd urn:bepado:api:integer http://api.bepado.com/schema/integer.xsd urn:bepado:api:boolean http://api.bepado.com/schema/boolean.xsd urn:bepado:api:string http://api.bepado.com/schema/string.xsd urn:bepado:api:null http://api.bepado.com/schema/null.xsd">
  <service>ProductService</service>
  <command>testProduct</command>
  <arguments xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
    <rpcerror xmlns="urn:bepado:api:rpcerror" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" struct="Shopware\Connect\Struct\RpcError" xsi:schemaLocation="urn:bepado:api:rpcerror http://api.bepado.com/schemas/rpcerror.xsd">
      <message>
        <string:string>{$message}</string:string>
      </message>
      <code>
        <integer:integer>0</integer:integer>
      </code>
    </rpcerror>
  </arguments>
</rpc>
XML;

        echo $xml;
    }
}
