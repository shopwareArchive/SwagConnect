<?php
/**
 * This file is part of the Shopware Connect SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Shopware\Connect\Struct\Metric;

use \Shopware\Connect\Struct\Metric;

/**
 * Time metric
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
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
