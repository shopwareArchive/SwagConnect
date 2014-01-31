<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * @version $Revision$
 */

namespace Bepado\Common\Struct\Metric;

use \Bepado\Common\Struct\Metric;

/**
 * Time metric
 *
 * @version $Revision$
 */
class Time extends Metric
{
    /**
     * Time value
     *
     * @var int
     */
    public $time;

    public function __toString()
    {
        return sprintf(
            " METRIC_TIME metric=%s value=%F ",
            $this->name,
            $this->time
        );
    }
}
