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
 * Converts any type of exception into an error struct
 */
class ExceptionToErrorConverter extends Converter
{
    /**
     * Converts the given $object to an \Exception.
     *
     * @param mixed $object
     * @return mixed
     */
    public function convertObject($object)
    {
        if ($object instanceof \Exception) {
            return new Error(
                array(
                    'message' => $object->getMessage(),
                    'code' => $object->getCode(),
                )
            );
        }

        return $object;
    }
}
