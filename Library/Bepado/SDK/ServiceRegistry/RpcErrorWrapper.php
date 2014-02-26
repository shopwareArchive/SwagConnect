<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * @version 1.0.129
 */

namespace Bepado\SDK\ServiceRegistry;

use Bepado\SDK\ErrorHandler as SDKErrorHandler;
use Bepado\Common\Rpc\ErrorHandler as RpcErrorHandler;

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
