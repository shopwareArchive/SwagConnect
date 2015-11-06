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
            return new RpcError(
                array(
                    'message' => $object->getMessage(),
                    'code' => $object->getCode(),
                )
            );
        }

        return $object;
    }
}
