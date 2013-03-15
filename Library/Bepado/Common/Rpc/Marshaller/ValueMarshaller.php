<?php
/**
 * This file is part of the Bepado Common Component.
 *
 * @version 1.0.0snapshot201303151129
 */

namespace Bepado\Common\Rpc\Marshaller;

interface ValueMarshaller
{
    /**
     * @param mixed $value
     * @return \DOMDocumentFragment
     */
    public function marshal($value);
}
