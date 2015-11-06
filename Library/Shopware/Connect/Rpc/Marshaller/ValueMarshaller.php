<?php
/**
 * This file is part of the Shopware Connect SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Shopware\Connect\Rpc\Marshaller;

interface ValueMarshaller
{
    /**
     * @param mixed $value
     * @return \DOMDocumentFragment
     */
    public function marshal($value);
}
