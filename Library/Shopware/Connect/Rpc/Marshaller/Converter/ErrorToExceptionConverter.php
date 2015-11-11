<?php
/**
 * This file is part of the Shopware Connect SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Shopware\Connect\Rpc\Marshaller\Converter;

use Shopware\Connect\Struct\RpcError;

use Shopware\Connect\Rpc\Marshaller\Converter;

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
