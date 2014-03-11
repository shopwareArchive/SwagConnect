<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * @version 1.1.142
 */

namespace Bepado\SDK\Struct;

use \Bepado\SDK\Struct;

/**
 * Base class for metric structs
 *
 * @version 1.1.142
 */
abstract class Metric extends Struct
{
    /**
     * @var string
     */
    public $name;
}
