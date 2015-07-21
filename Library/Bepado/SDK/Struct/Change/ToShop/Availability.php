<?php

namespace Bepado\SDK\Struct\Change\ToShop;

use Bepado\SDK\Struct\Change;

/**
 * Availability of Product has changed in FromShop.
 */
class Availability extends Change
{
    /**
     * New availability for product
     *
     * @var int
     */
    public $availability;
}
