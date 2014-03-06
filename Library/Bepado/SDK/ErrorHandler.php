<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * @version 1.1.133
 */

namespace Bepado\SDK;

/**
 * Base class for error handler implementations
 *
 * @version 1.1.133
 */
abstract class ErrorHandler
{
    /**
     * Handle error
     *
     * @param Struct\Error $error
     * @return void
     */
    abstract public function handleError(Struct\Error $error);

    /**
     * Handle exception
     *
     * @param \Exception $exception
     * @return void
     */
    abstract public function handleException(\Exception $exception);

    /**
     * Notify error handler about \Bepado\SDK\SDK#handle shutdown.
     *
     * @param string $messsage
     */
    public function notifyRpcShutdown($message)
    {
    }
}
