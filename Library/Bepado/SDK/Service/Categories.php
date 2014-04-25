<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Bepado\SDK\Service;

use Bepado\SDK\Gateway;
use Bepado\SDK\HttpClient;
use Bepado\SDK\Struct;

/**
 * Service to store category updates
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */
class Categories
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

    /**
     * Get the categories last revision.
     *
     * @return string
     */
    public function lastRevision()
    {
        return $this->configuration->getCategoriesLastRevision();
    }

    /**
     * Update the categories in this shop and increment the last revision.
     *
     * @param array $changes
     * @return void
     */
    public function replicate(array $changes)
    {
        foreach ($changes as $change) {
            $this->configuration->setCategories($change['categories']);
            $this->configuration->setCategoriesLastRevision($change['revision']);
        }
    }
}
