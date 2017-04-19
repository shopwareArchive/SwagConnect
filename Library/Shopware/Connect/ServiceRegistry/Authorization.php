<?php

namespace Shopware\Connect\ServiceRegistry;

use Shopware\Connect\Rpc;
use Shopware\Connect\Struct;

use Shopware\Connect\SDK;
use Shopware\Connect\Struct\AuthenticationToken;
use Shopware\Connect\SecurityException;

/**
 * Authorization for the different RPC based services.
 */
class Authorization extends Rpc\ServiceRegistry
{
    /**
     * Inner service registry
     *
     * @var Rpc\ServiceRegistry
     */
    protected $serviceRegistry;

    /**
     * Authenticated User-Identifier
     *
     * @var AuthenticationToken
     */
    protected $token;

    /**
     * Construct from inner service registry
     *
     * @param Rpc\ServiceRegistry $serviceRegistry
     * @param AuthenticationToken $token
     */
    public function __construct(Rpc\ServiceRegistry $serviceRegistry, AuthenticationToken $token)
    {
        $this->serviceRegistry = $serviceRegistry;
        $this->token = $token;
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
        switch ($rpcCall->service) {
            case 'products':
            case 'categories':
            case 'configuration':
            case 'productPayments':
            case 'product_payments_from_shop':
            case 'shippingCosts':
                if ($this->token->userIdentifier !== "connect") {
                    throw new SecurityException("No authorization to call 'products' or 'configuration' service.");
                }
                break;

            case 'transaction':
                if ($rpcCall->command === 'reserve' &&
                    $this->token->userIdentifier !== $rpcCall->arguments[0]->orderShop) {
                    throw new SecurityException(
                        "No authorization to call 'transaction.reserve' for a different order shop."
                    );
                }

                // second argument to checkProducts() is the shop the order is happening in.
                if ($rpcCall->command === 'checkProducts') {
                    if ($this->token->userIdentifier != $rpcCall->arguments[1]) {
                        throw new SecurityException(
                            "No authorization to call 'transaction.checkProducts' for a different order shop."
                        );
                    }
                }
                break;

            default:
                throw new SecurityException(
                    sprintf(
                        "No Authorization to call service '%s'.",
                        $rpcCall->service
                    )
                );
        }

        return $this->serviceRegistry->dispatch($rpcCall);
    }
}
