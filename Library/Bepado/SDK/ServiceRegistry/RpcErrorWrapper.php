<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Bepado\SDK\ServiceRegistry;

use Bepado\SDK\ErrorHandler as SDKErrorHandler;
use Bepado\SDK\Rpc\ErrorHandler as RpcErrorHandler;

/**
 * Wrapper for RpcErrorHandler to notify SDK error handler about pending shutdown.
 */
class RpcErrorWrapper extends RpcErrorHandler
{
    public function __construct(SDKErrorHandler $sdkErrorHandler, RpcErrorHandler $rpcErrorHandler)
    {
        $this->sdkErrorHandler = $sdkErrorHandler;
        $this->rpcErrorHandler = $rpcErrorHandler;
    }

    public function renderResponse($message)
    {
        $this->sdkErrorHandler->notifyRpcShutdown($message);
        $this->rpcErrorHandler->renderResponse($message);
    }
}
