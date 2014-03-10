<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * @version 1.1.142
 */

namespace Bepado\SDK\Struct\Change\FromShop;

use Bepado\SDK\Struct\Change;
use Bepado\SDK\Struct\Product;

/**
 * Insert change struct
 *
 * @version 1.1.142
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
