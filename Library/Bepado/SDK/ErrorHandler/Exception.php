<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * @version 1.1.141
 */

namespace Bepado\SDK\ErrorHandler;

use Bepado\SDK\ErrorHandler;
use Bepado\SDK\Struct;

/**
 * Base class for error handler implementations
 *
 * @version 1.1.141
 */
class Exception extends ErrorHandler
{
    /**
     * Handle error
     *
     * @param Struct\Error $error
     * @return void
     */
    public function handleError(Struct\Error $error)
    {
        throw new \Exception($error->message);
    }

    /**
     * Handle exception
     *
     * @param \Exception $exception
     * @return void
     */
    public function handleException(\Exception $exception)
    {
        throw $exception;
    }
}
