<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * @version 1.0.0snapshot201303151129
 */

namespace Bepado\SDK\Struct\Change\InterShop;

use Bepado\SDK\Struct\Change;
use Bepado\SDK\Struct\Product;

/**
 * Update change struct
 *
 * @version 1.0.0snapshot201303151129
 * @api
 */
class Update extends Change
{
    /**
     * Updated product
     *
     * @var Product
     */
    public $product;

    /**
     * Old version of Product
     *
     * @var Product
     */
    public $oldProduct;
}
