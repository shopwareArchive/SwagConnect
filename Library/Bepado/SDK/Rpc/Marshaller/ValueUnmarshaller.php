<?php
/**
 * This file is part of the Bepado Common Component.
 *
 * @version 1.1.142
 */

namespace Bepado\SDK\Rpc\Marshaller;

interface ValueUnmarshaller
{
    /**
     * @param \DOMElement $element
     * @return mixed
     */
    public function unmarshal(\DOMElement $element);
}
