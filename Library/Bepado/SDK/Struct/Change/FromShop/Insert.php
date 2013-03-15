<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * @version 1.0.0snapshot201303151129
 */

namespace Bepado\SDK\Struct\Change\FromShop;

use Bepado\SDK\Struct\Change;
use Bepado\SDK\Struct\Product;

/**
 * Insert change struct
 *
 * @version 1.0.0snapshot201303151129
 * @api
 */
class Insert extends Change
{
    /**
     * New product
     *
     * @var Product
     */
    public $product;
}
