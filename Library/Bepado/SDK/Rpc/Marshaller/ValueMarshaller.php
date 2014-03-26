<?php
/**
 * This file is part of the Bepado Common Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Bepado\SDK\Rpc\Marshaller;

interface ValueMarshaller
{
    /**
     * @param mixed $value
     * @return \DOMDocumentFragment
     */
    public function marshal($value);
}
