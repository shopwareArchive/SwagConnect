<?php
/**
 * This file is part of the Shopware Connect SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Shopware\Connect\Service;

use Shopware\Connect\Gateway;
use Shopware\Connect\HttpClient;
use Shopware\Connect\Struct;
use Shopware\Connect\SDK;

/**
 * Service to store configuration updates
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
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
     * Construct from gateway
     *
     * @param Gateway\ShopConfiguration $gateway
     */
    public function __construct(Gateway\ShopConfiguration $configuration)
    {
        $this->configuration = $configuration;
    }

    public function replicate(array $changes)
    {
        foreach ($changes as $change) {
            $config = $change['configuration'];

            foreach ($config->shops as $shop) {
                $this->configuration->setShopConfiguration($shop->name, $shop);
            }

            $this->configuration->setBillingAddress($config->billingAddress);
            $this->configuration->setFeatures($config->features);
            $this->updatePriceType($config->priceType);
        }
    }

    private function updatePriceType($priceType)
    {
        if (!$priceType) {
            return;
        }

        $disallowChange = array(SDK::PRICE_TYPE_PURCHASE, SDK::PRICE_TYPE_RETAIL, SDK::PRICE_TYPE_BOTH);
        $validPriceTypes = $disallowChange + array(SDK::PRICE_TYPE_NONE);

        if (!in_array($priceType, $validPriceTypes)) {
            return;
        }

        if (in_array($this->configuration->getConfig(SDK::CONFIG_PRICE_TYPE), $disallowChange)) {
            return;
        }

        $this->configuration->setConfig(SDK::CONFIG_PRICE_TYPE, $priceType);
    }

    public function lastRevision()
    {
        return 0; // always replicate
    }
}
