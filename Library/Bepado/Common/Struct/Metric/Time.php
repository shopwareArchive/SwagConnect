<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * @version 1.0.0snapshot201303151129
 */

namespace Bepado\Common\Struct\Metric;

use \Bepado\Common\Struct\Metric;

/**
 * Time metric
 *
 * @version 1.0.0snapshot201303151129
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
