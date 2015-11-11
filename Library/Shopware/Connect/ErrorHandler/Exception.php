<?php
/**
 * This file is part of the Shopware Connect SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Shopware\Connect\ErrorHandler;

use Shopware\Connect\ErrorHandler;
use Shopware\Connect\Struct;
use Shopware\Connect\Exception\RemoteException;

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
