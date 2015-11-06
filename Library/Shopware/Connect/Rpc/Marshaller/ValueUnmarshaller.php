<?php
/**
 * This file is part of the Shopware Connect SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Shopware\Connect\Rpc\Marshaller;

interface ValueUnmarshaller
{
    /**
     * @param \DOMElement $element
     * @return mixed
     */
    public function unmarshal(\DOMElement $element);
}
