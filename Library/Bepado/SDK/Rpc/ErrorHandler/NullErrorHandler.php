<?php
/**
 * This file is part of the Bepado SDK component.
 *
 * @version 1.1.142
 */

namespace Bepado\SDK\Rpc\ErrorHandler;

use Bepado\SDK\Rpc\ErrorHandler;

/**
 * Skip error displaying
 */
class NullErrorHandler extends ErrorHandler
{
    public function renderResponse($message)
    {
    }
}
