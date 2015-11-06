<?php

namespace Shopware\Connect\Struct\Change\ToShop;

use Shopware\Connect\Struct\Change;

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
