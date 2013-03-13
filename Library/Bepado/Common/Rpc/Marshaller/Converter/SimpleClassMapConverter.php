<?php
/**
 * This file is part of the Bepado Common Component.
 *
 * @version 1.0.0snapshot201303061109
 */

namespace Bepado\Common\Rpc\Marshaller\Converter;

use Bepado\Common\Rpc\Marshaller\Converter;

/**
 * The SimpleClassMapConverter converts between different classes.
 *
 * This one is mostly used for struct classes, but will work on others as well.
 * An iteration through all properties on the original class is executed. If the corresponding property is defined
 * on the target class as well it will be copied over. Otherwise it will be ignored.
 *
 * For this to work the target class needs to be instantiable without any arguments to the constructor
 */
class SimpleClassMapConverter extends Converter
{
    /**
     * @var string[]
     */
    private $translationTable = array();

    /**
     * Construct a SimpleClassMapConverter using a defined TranslationTable
     *
     * The TranslationTable has to be an array which maps one classname to another, eg.:
     * <code>
     *   array(
     *     '\\One\\Cool\\Class' => '\\Another\\Cool\\Class\\Maybe\\In\\Another\\Namespace',
     *     ...
     *   );
     * </code>
     *
     * @param string[] $translationTable
     */
    public function __construct($translationTable)
    {
        $this->translationTable = $translationTable;
    }

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
     * @throws \OutOfRangeException if no translation entry could be found for the given object
     * @return mixed
     */
    public function convertObject($object)
    {
        $originalClass = get_class($object);
        if (!isset($this->translationTable[$originalClass])) {
            return $object;
        }
        $targetClass = $this->translationTable[$originalClass];
        $target = new $targetClass();

        $originalProperties = get_object_vars($object);
        $targetProperties = get_object_vars($target);

        foreach ($originalProperties as $property => $value) {
            if (array_key_exists($property, $targetProperties) !== true) {
                // The property does not exist on the target, therefore ignore it
                continue;
            }
            $target->$property = $value;
        }

        return $target;
    }
}
