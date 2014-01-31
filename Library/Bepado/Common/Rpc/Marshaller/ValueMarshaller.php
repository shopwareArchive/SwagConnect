<?php
/**
 * This file is part of the Bepado Common Component.
 *
 * @version $Revision$
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
