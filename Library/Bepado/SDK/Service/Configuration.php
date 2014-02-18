<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * @version $Revision$
 */

namespace Bepado\SDK\Service;

use Bepado\SDK\Gateway;
use Bepado\SDK\HttpClient;
use Bepado\SDK\Struct;

/**
 * Service to store configuration updates
 *
 * @version $Revision$
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

    /**
     * Store shop configuration updates
     *
     * @return void
     */
    public function update(array $configurations)
    {
        foreach ($configurations as $configuration) {
            $this->configuration->setShopConfiguration(
                $configuration['shopId'],
                new Struct\ShopConfiguration(
                    array(
                        'serviceEndpoint'  => $configuration['serviceEndpoint'],
                        'shippingCost'     => $configuration['shippingCost'],
                        'displayName'      => $configuration['shopDisplayName'],
                        'url'              => $configuration['shopUrl'],
                        'key'              => $configuration['key'],
                        'priceGroupMargin' => $configuration['priceGroupMargin'],
                    )
                )
            );
        }
    }

    /**
     * Get the categories last revision.
     *
     * @return string
     */
    public function getCategoriesLastRevision()
    {
        return $this->configuration->getCategoriesLastRevision();
    }

    /**
     * Update the categories in this shop and increment the last revision.
     *
     * @param array $categories
     * @return void
     */
    public function updateCategories(array $categories, $revision)
    {
        if (strcmp($this->getCategoriesLastRevision(), $revision) > 0) {
            return;
        }

        $this->configuration->setCategories($categories);
        $this->configuration->setCategoriesLastRevision($revision);
    }
}
