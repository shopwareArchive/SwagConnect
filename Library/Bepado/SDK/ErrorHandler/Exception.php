<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Bepado\SDK\ErrorHandler;

use Bepado\SDK\ErrorHandler;
use Bepado\SDK\Struct;
use Bepado\SDK\Exception\RemoteException;

/**
 * Base class for error handler implementations
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
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
        throw new RemoteException($error->message);
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
