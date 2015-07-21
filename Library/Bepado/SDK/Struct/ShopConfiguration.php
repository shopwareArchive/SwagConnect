<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Bepado\SDK\Struct;

use Bepado\SDK\Struct;

/**
 * Struct class for shop configurations
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 * @api
 */
class ShopConfiguration extends Struct
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $serviceEndpoint;

    /**
     * @var float
     */
    public $shippingCost;

    /**
     * @var string
     */
    public $displayName;

    /**
     * @var string
     */
    public $url;

    /**
     * @var string
     */
    public $key;

    /**
     * Restores a shop configuration from a previously stored state array.
     *
     * @param array $state
     * @return \Bepado\SDK\Struct\ShopConfiguration
     */
    public static function __set_state(array $state)
    {
        unset($state['priceGroupMargin']);

        return new ShopConfiguration($state);
    }
}
