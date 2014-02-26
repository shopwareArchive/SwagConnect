<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * @version 1.0.129
 */

namespace Bepado\SDK\RevisionProvider;

use Bepado\SDK\RevisionProvider;

/**
 * Time and iteration based revision provider, which provides ordered revisions 
 * for non-clustered systems.
 *
 * @version 1.0.129
 */
class Time extends RevisionProvider
{
    /**
     * Start time of current run
     *
     * @var int
     */
    protected $time = null;

    /**
     * Current iteration
     *
     * @var int
     */
    protected $iteration = 0;

    /**
     * Get next revision
     *
     * @return string
     */
    public function next()
    {
        if (!isset($time)) {
            $this->time = microtime(true);
        }

        return sprintf('%.5f%05d', $this->time, $this->iteration++);
    }
}
