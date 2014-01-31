<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * @version $Revision$
 */

namespace Bepado\Common\Struct;

use \Bepado\Common\Struct;

/**
 * Base class for metric structs
 *
 * @version $Revision$
 */
abstract class Metric extends Struct
{
    /**
     * @var string
     */
    public $name;
}
