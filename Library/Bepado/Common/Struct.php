<?php
/**
 * This file is part of the Bepado Common component.
 *
 * @version $Revision$
 */

namespace Bepado\Common;

/**
 * Base class for data structs.
 */
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
        throw new \OutOfRangeException("Unknown property \${$name}.");
    }

    public function __set($name, $value)
    {
        throw new \OutOfRangeException("Unknown property \${$name}.");
    }

    public function __unset($name)
    {
        throw new \OutOfRangeException("Unknown property \${$name}.");
    }
}
