<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * @version 1.0.0snapshot201303151129
 */

namespace Bepado\SDK\Service;

use Bepado\SDK\Gateway;
use Bepado\SDK\Struct;

/**
 * Service to store configuration updates
 *
 * @version 1.0.0snapshot201303151129
 */
class Configuration
{
    /**
     * Gateway to shop configuration
     *
     * @var Gateway\ShopConfiguration
     */
    protected $configuration;

    /**
     * Struct verificator
     *
     * @var Struct\VerificatorDispatcher
     */
    protected $verificator;

    /**
     * Construct from gateway
     *
     * @param Gateway\ShopConfiguration $gateway
     * @param Struct\VerificatorDispatcher $verificator
     * @return void
     */
    public function __construct(
        Gateway\ShopConfiguration $configuration,
        Struct\VerificatorDispatcher $verificator
    ) {
        $this->configuration = $configuration;
        $this->verificator = $verificator;
    }

    /**
     * Store shop configuration updates
     *
     * @param Struct\ShopConfiguration $shopConfigurations
     * @return void
     */
    public function update(array $shopConfigurations)
    {
        foreach ($shopConfigurations as $shopId => $shopConfiguration) {
            $this->verificator->verify($shopConfiguration);
            $this->configuration->setShopConfiguration($shopId, $shopConfiguration);
        }
    }
}
