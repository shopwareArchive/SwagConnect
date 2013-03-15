<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * @version 1.0.0snapshot201303151129
 */

namespace Bepado\SDK\ShopFactory;

use Bepado\SDK\ShopFactory;
use Bepado\SDK\ShopGateway;
use Bepado\SDK\Gateway;

/**
 * Shop factory
 *
 * Constructs gateways to interact with other shops
 *
 * @version 1.0.0snapshot201303151129
 */
class Http extends ShopFactory
{
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
        Gateway\ShopConfiguration $configuration
    ) {
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
        return new ShopGateway\Http($configuration->serviceEndpoint);
    }
}
