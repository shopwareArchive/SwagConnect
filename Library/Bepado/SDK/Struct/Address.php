<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * @version 1.0.0snapshot201303061109
 */

namespace Bepado\SDK\Struct;

use Bepado\SDK\Struct;

/**
 * Struct class representing an address
 *
 * @version 1.0.0snapshot201303061109
 * @api
 */
class Address extends Struct
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $line1;

    /**
     * @var string
     */
    public $line2;

    /**
     * @var string
     */
    public $zip;

    /**
     * @var string
     */
    public $city;

    /**
     * @var string
     */
    public $state;

    /**
     * @var string
     */
    public $country;

    /**
     * Restores an address from a previously stored state array.
     *
     * @param array $state
     * @return \Bepado\SDK\Struct\Address
     */
    public static function __set_state(array $state)
    {
        return new Address($state);
    }
}
