<?php
/**
 * This file is part of the Bepado Common component.
 *
 * @version 1.0.129
 */

namespace Bepado\Common\Rpc\ErrorHandler;

use Bepado\Common\Rpc\ErrorHandler;

/**
 * Skip error displaying
 */
class NullErrorHandler extends ErrorHandler
{
    public function renderResponse($message)
    {
    }
}
