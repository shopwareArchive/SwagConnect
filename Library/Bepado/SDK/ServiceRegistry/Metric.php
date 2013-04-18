<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * @version $Revision$
 */

namespace Bepado\SDK\ServiceRegistry;

use Bepado\Common\Rpc;
use Bepado\Common\Struct;

use Bepado\SDK\SDK;

/**
 * Service registry, which measures calls and wraps responses
 */
class Metric extends Rpc\ServiceRegistry
{
    /**
     * Inner service registry
     *
     * @var Rpc\ServiceRegistry
     */
    protected $serviceRegistry;

    /**
     * Metric callbacks for certain RPC calls
     *
     * @var array
     */
    protected $metrics = array();

    /**
     * Construct from inner service registry
     *
     * @param Rpc\ServiceRegistry $serviceRegistry
     */
    public function __construct(Rpc\ServiceRegistry $serviceRegistry)
    {
        $this->serviceRegistry = $serviceRegistry;
    }

    /**
     * @param string $name
     * @param array $commands
     * @param object $provider
     */
    public function registerService($name, array $commands, $provider)
    {
        return $this->serviceRegistry->registerService($name, $commands, $provider);
    }

    /**
     * @param string $name
     * @param string $command
     * @return array
     * @throws \UnexpectedValueException
     */
    public function getService($name, $command)
    {
        return $this->serviceRegistry->getService($name, $command);
    }

    /**
     * @param string $name
     * @param string $command
     * @param object $provider
     */
    public function registerMetric($name, $command, $provider)
    {
        $this->metrics[$name][$command] = $provider;
    }

    /**
     * Get registered metrics for given RPC call
     *
     * @param Struct\RpcCall $rpcCall
     * @return Struct\Metric[]
     */
    protected function getMetrics(Struct\RpcCall $rpcCall)
    {
        if (!isset($this->metrics[$rpcCall->service]) ||
            !isset($this->metrics[$rpcCall->service][$rpcCall->command])) {
            return array();
        }

        return call_user_func_array(
            array(
                $this->metrics[$rpcCall->service][$rpcCall->command],
                $rpcCall->command
            ),
            $rpcCall->arguments
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
        $start = microtime(true);

        $response = new Struct\Response(
            array(
                'result' => $this->serviceRegistry->dispatch($rpcCall),
                'metrics' => $this->getMetrics($rpcCall),
                'version' => SDK::VERSION,
            )
        );

        $response->metrics[] = new Struct\Metric\Time(
            array(
                'name' => 'sdk.' . $rpcCall->service . '.' . $rpcCall->command,
                'time' => microtime(true) - $start,
            )
        );

        return $response;
    }
}
