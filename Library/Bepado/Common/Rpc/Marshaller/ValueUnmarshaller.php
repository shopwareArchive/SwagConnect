<?php
/**
 * This file is part of the Bepado Common Component.
 *
 * @version 1.0.0snapshot201303151129
 */

namespace Bepado\Common\Rpc\Marshaller;

interface ValueUnmarshaller
{
    /**
     * @param \DOMElement $element
     * @return mixed
     */
    public function unmarshal(\DOMElement $element);
}
