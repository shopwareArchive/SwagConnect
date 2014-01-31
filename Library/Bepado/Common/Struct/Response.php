<?php
/**
 * This file is part of the Bepado Common Component.
 *
 * @version $Revision$
 */

namespace Bepado\Common\Struct;

use Bepado\Common\Struct;
use Bepado\Common\Common;

/**
 * Struct class representing a message
 *
 * @version $Revision$
 * @api
 */
class Response extends Struct
{
    /**
     * Result of the response
     *
     * Can be about anything
     *
     * @var mixed
     */
    public $result;

    /**
     * Metrics
     *
     * Array of metrics, transmitted with the response. Will be logged by the
     * updater.
     *
     * @var array
     */
    public $metrics = array();

    /**
     * Version string of the answering component
     *
     * @var string
     */
    public $version;
}
