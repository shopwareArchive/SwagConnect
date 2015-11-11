<?php
/**
 * This file is part of the Shopware Connect SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Shopware\Connect;

/**
 * Base class for error handler implementations
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
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
     * Notify error handler about \Shopware\Connect\SDK#handle shutdown.
     *
     * @param string $messsage
     */
    public function notifyRpcShutdown($message)
    {
    }
}
