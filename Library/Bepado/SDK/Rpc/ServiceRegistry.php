<?php
/**
 * This file is part of the Bepado SDK component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Bepado\SDK\Rpc;

use Bepado\SDK\Struct;

/**
 * RPC service registry
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */
class ServiceRegistry
{
    private $services = array();
    private $errorHandler;

    public function __construct(ErrorHandler $errorHandler = null)
    {
        $this->errorHandler = $errorHandler ?: new ErrorHandler\NullErrorHandler();
    }

    /**
     * @param string $name
     * @param array $commands
     * @param object $provider
     */
    public function registerService($name, array $commands, $provider)
    {
        $this->services[$name] = array(
            "provider" => $provider,
            "commands" => $commands,
        );
    }

    /**
     * @param string $name
     * @param string $command
     * @return array
     * @throws \UnexpectedValueException
     */
    public function getService($name, $command)
    {
        if (!isset($this->services[$name])) {
            throw new \UnexpectedValueException("The requested service '{$name}' is unknown.");
        }

        if (!in_array($command, $this->services[$name]["commands"])) {
            throw new \UnexpectedValueException("The requested command '{$command}' is unknown for service '{$name}'");
        }

        return array(
            "provider" => $this->services[$name]["provider"],
            "command" => $command,
        );
    }

    /**
     * Dispatch RPC call
     *
     * Dispatches RPC call to involved service. Returns the return value from
     * the given service.
     *
     * @param Struct\RpcCall $rpcCall
     * @return mixed
     */
    public function dispatch(Struct\RpcCall $rpcCall)
    {
        $this->errorHandler->registerHandlers();
        $service = $this->getService($rpcCall->service, $rpcCall->command);

        try {
            $response = call_user_func_array(
                array($service['provider'], $service['command']),
                $rpcCall->arguments
            );
        } catch (\Exception $e) {
            $this->errorHandler->restore();
            throw $e;
        }

        $this->errorHandler->restore();

        return $response;
    }
}
