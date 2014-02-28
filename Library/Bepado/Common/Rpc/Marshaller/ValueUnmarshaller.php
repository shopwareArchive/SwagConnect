<?php
/**
 * This file is part of the Bepado Common Component.
 *
 * @version 1.1.133
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
