<?php
/**
 * This file is part of the Shopware Connect SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Shopware\Connect\ShopFactory;

use Shopware\Connect\ShopFactory;
use Shopware\Connect\ShopGateway;
use Shopware\Connect\Gateway;
use Shopware\Connect\DependencyResolver;
use Shopware\Connect\ShopGateway\ShopRequestSigner;

/**
 * Shop factory
 *
 * Constructs gateways to interact with other shops
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */
class Http extends ShopFactory
{
    /**
     * @var Shopware\Connect\DependencyResolver
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
            $this->dependencyResolver->getUnmarshaller(),
            new ShopRequestSigner($this->dependencyResolver->getRequestSigner(), $shopId)
        );
    }
}
