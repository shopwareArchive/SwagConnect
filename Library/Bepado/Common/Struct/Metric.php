<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * @version 1.0.0snapshot201303151129
 */

namespace Bepado\Common\Struct;

use \Bepado\Common\Struct;

/**
 * Base class for metric structs
 *
 * @version 1.0.0snapshot201303151129
 */
abstract class Metric extends Struct
{
    /**
     * @var string
     */
    public $name;
}
