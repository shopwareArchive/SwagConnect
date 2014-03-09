<?php
/**
 * This file is part of the Bepado Common Component.
 *
 * @version 1.1.141
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
