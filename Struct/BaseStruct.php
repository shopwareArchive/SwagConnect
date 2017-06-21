<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Struct;

abstract class BaseStruct
{
    public function __construct(array $values = [])
    {
        foreach ($values as $name => $value) {
            $this->$name = $value;
        }
    }

    public function __get($name)
    {
        throw new \OutOfRangeException("Unknown property \${$name} in " . get_class($this) . '.');
    }

    public function __set($name, $value)
    {
        throw new \OutOfRangeException("Unknown property \${$name} in " . get_class($this) . '.');
    }

    public function __unset($name)
    {
        throw new \OutOfRangeException("Unknown property \${$name} in " . get_class($this) . '.');
    }

    public function __clone()
    {
        foreach ($this as $property => $value) {
            if (is_object($value)) {
                $this->$property = clone $value;
            }

            if (is_array($value)) {
                $this->cloneArray($this->$property);
            }
        }
    }

    /**
     * Clone array
     *
     * @param array $array
     */
    private function cloneArray(array &$array)
    {
        foreach ($array as $key => $value) {
            if (is_object($value)) {
                $array[$key] = clone $value;
            }

            if (is_array($value)) {
                $this->cloneArray($array[$key]);
            }
        }
    }

    /**
     * Restores struct from a previously stored state array.
     *
     * @param array $state
     *
     * @return static
     */
    public static function __set_state(array $state)
    {
        return new static($state);
    }
}
