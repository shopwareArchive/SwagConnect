<?php
namespace Bepado\SDK;

abstract class Struct
{
    public function __construct(array $values = array())
    {
        foreach ($values as $name => $value) {
            $this->$name = $value;
        }
    }

    public function __get($name)
    {
        throw new \OutOfRangeException("Unknown property \${$name} in " . get_class($this) . ".");
    }

    public function __set($name, $value)
    {
        throw new \OutOfRangeException("Unknown property \${$name} in " . get_class($this) . ".");
    }

    public function __unset($name)
    {
        throw new \OutOfRangeException("Unknown property \${$name} in " . get_class($this) . ".");
    }
}
