<?php
/**
 * This file is part of the Bepado Common Component.
 *
 * @version 1.1.141
 */

namespace Bepado\SDK\Rpc\Marshaller\Converter;

use Bepado\SDK\Struct\RpcError;

use Bepado\SDK\Rpc\Marshaller\Converter;

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
        if ($object instanceof RpcError) {
            throw new \Exception(
                $object->message,
                $object->code
            );
        }

        return $object;
    }
}
