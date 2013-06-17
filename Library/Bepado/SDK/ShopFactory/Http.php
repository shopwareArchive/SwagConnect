<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * @version $Revision$
 */

namespace Bepado\SDK\ShopFactory;

use Bepado\SDK\ShopFactory;
use Bepado\SDK\ShopGateway;
use Bepado\SDK\Gateway;
use Bepado\SDK\DependencyResolver;

/**
 * Shop factory
 *
 * Constructs gateways to interact with other shops
 *
 * @version $Revision$
 */
class Http extends ShopFactory
{
    /**
     * @var Bepado\SDK\DependencyResolver
     */
    protected $dependencyResolver;

    /**
     * Gateway to shop configuration
     *
     * @var Gateway\ShopConfiguration
     */
    protected $configuration;

    /**
     * Construct from gateway
     *
     * @param Gateway\ShopConfiguration $gateway
     * @return void
     */
    public function __construct(
        DependencyResolver $dependencyResolver,
        Gateway\ShopConfiguration $configuration
    ) {
        $this->dependencyResolver = $dependencyResolver;
        $this->configuration = $configuration;
    }

    /**
     * Get shop gateway for shop
     *
     * @param string $shopId
     * @return ShopGateway
     */
    public function getShopGateway($shopId)
    {
        $configuration = $this->configuration->getShopConfiguration($shopId);

        return new ShopGateway\Http(
            $this->dependencyResolver->getHttpClient(
                $configuration->serviceEndpoint
            ),
            $this->dependencyResolver->getMarshaller(),
            $this->dependencyResolver->getUnmarshaller()
        );
    }
}
