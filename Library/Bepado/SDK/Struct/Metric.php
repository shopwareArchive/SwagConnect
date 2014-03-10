<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * @version 1.1.141
 */

namespace Bepado\SDK\Struct;

use \Bepado\SDK\Struct;

/**
 * Base class for metric structs
 *
 * @version 1.1.141
 */
abstract class Metric extends Struct
{
    /**
     * @var string
     */
    public $name;
}
