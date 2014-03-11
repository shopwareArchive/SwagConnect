<?php
/**
 * This file is part of the Bepado Common Component.
 *
 * @version 1.1.142
 */

namespace Bepado\SDK\Rpc\Marshaller;

/**
 * A converter can be hooked into a Marshaller and Unmarshaller, to convert given objects
 * into other representations before marshalling or returning them.
 *
 * Converters always work on php object representations. They will never see any of the marshalled information
 *
 * The whole conversion system is needed, as we do not want to have dependencies inside the SDK on any internal
 * Bepado data structure. Therefore the Bepado side has to convert the data during marshalling as well as unmarshalling
 *
 * @TODO: for now converters only handle objects. Maybe in the future a conversion for scalars does make sense as well
 */
abstract class Converter
{
    /**
     * Convert the given php object of arbitrary type to another one and return it.
     *
     * There are two possible scenarios, when this will affect the process:
     *
     * Marshalling:
     *   - The returned object will be marshalled instead of the original one.
     *
     * Unmarshalling:
     *   - The original object has been unmarshalled, but instead of it the returned object will be
     *     provided by the unmarshaller to its caller
     *
     * @param mixed $object
     * @return mixed
     */
    abstract public function convertObject($object);
}
