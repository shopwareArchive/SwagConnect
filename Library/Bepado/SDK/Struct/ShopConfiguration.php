<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * @version 1.0.129
 */

namespace Bepado\SDK\Struct;

use Bepado\SDK\Struct;

/**
 * Struct class for shop configurations
 *
 * @version 1.0.129
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
     * Additional margin on top of purchase price in percent.
     *
     * Defaults to 0, where only the purchase price is used.
     * Margin is calculated on top of purchase price.
     *
     * @var int
     */
    public $priceGroupMargin;

    /**
     * Restores a shop configuration from a previously stored state array.
     *
     * @param array $state
     * @return \Bepado\SDK\Struct\ShopConfiguration
     */
    public static function __set_state(array $state)
    {
        return new ShopConfiguration($state);
    }
}
