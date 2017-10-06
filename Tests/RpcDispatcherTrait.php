<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Tests;

trait RpcDispatcherTrait
{
    /**
     * @param string $service
     * @param string $command
     * @param array $args
     * @return mixed
     */
    public function dispatchRpcCall($service, $command, array $args)
    {
        $sdk = Shopware()->Container()->get('ConnectSDK');
        $refl = new \ReflectionObject($sdk);
        $property = $refl->getProperty('dependencies');
        $property->setAccessible(true);
        $deps = $property->getValue($sdk);
        $serviceRegistry = $deps->getServiceRegistry();
        $callable = $serviceRegistry->getService($service, $command);

        return call_user_func_array([$callable['provider'], $callable['command']], $args);
    }
}
