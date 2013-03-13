<?php
/**
 * This file is part of the Bepado Common Component.
 *
 * @version 1.0.0snapshot201303061109
 */

namespace Bepado\Common\Rpc\Marshaller\Converter;

use Bepado\Common\Struct\Error;

use Bepado\Common\Rpc\Marshaller\Converter;

/**
 * Converts an error struct into an exception
 */
class ErrorToExceptionConverter extends Converter
{
    /**
     * Converts the given $object to an \Exception.
     *
     * @param mixed $object
     * @return mixed
     */
    public function convertObject($object)
    {
        if ($object instanceof \Bepado\Common\Struct\Error) {
            throw new \Exception(
                $object->message,
                $object->code
            );
        }

        return $object;
    }
}
