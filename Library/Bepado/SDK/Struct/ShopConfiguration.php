<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * @version $Revision$
 */

namespace Bepado\SDK\Struct;

use Bepado\SDK\Struct;

/**
 * Struct class for shop configurations
 *
 * @version $Revision$
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
    public $token;

    /**
     * @var float
     */
    public $shippingCost;

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
