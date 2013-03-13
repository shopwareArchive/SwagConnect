<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * @version 1.0.0snapshot201303061109
 */

namespace Bepado\SDK\Struct;

use Bepado\SDK\Struct;

/**
 * Struct class for shop configurations
 *
 * @version 1.0.0snapshot201303061109
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
