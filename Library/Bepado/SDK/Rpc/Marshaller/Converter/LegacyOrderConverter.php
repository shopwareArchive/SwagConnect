<?php
/**
 * This file is part of the Bepado Common Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Bepado\SDK\Rpc\Marshaller\Converter;

use Bepado\SDK\Rpc\Marshaller\Converter;
use Bepado\SDK\Struct\Order;

/**
 * The LegacyOrderConverter ensures orders are marshalled in a way the old SDK
 * are still able to receive the shipping costs from new SDKs.
 */
class LegacyOrderConverter extends Converter
{
    /**
     * Convert the order struct into the correct version
     *
     * Sets two properties (shippingCosts & grossShippingCosts) besides they do
     * not exist any more. This way the important values for legacy SDKs are
     * still there.
     *
     * @param mixed $object
     * @throws \OutOfRangeException if no translation entry could be found for the given object
     * @return mixed
     */
    public function convertObject($object)
    {
        if (!($object instanceof Order)) {
            return $object;
        }

        // This is an ugly hack to inject two properties into the object, we
        // need to be there for legacy reasons.
        $newObject = unserialize(
            sprintf(
                'O:%d:"%s":2:{s:13:"shippingCosts";%ss:18:"grossShippingCosts";%s}',
                strlen(get_class($object)),
                get_class($object),
                serialize($object->shipping->shippingCosts),
                serialize($object->shipping->grossShippingCosts)
            )
        );

        foreach ($object as $property => $value) {
            $newObject->$property = $value;
        }

        return $newObject;
    }
}
