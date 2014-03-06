<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * @version 1.1.133
 */

namespace Bepado\Common\Struct\Metric;

use \Bepado\Common\Struct\Metric;

/**
 * Count metric
 *
 * @version 1.1.133
 */
class Count extends Metric
{
    /**
     * Count value
     *
     * @var int
     */
    public $count;

    public function __toString()
    {
        return sprintf(
            " METRIC_COUNT metric=%s value=%d ",
            $this->name,
            $this->count
        );
    }
}
