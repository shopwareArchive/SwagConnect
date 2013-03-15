<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * @version 1.0.0snapshot201303151129
 */

namespace Bepado\SDK\Struct\Change\ToShop;

use Bepado\SDK\Struct\Change;
use Bepado\SDK\Struct\Product;

/**
 * Insert change struct
 *
 * @version 1.0.0snapshot201303151129
 * @api
 */
class InsertOrUpdate extends Change
{
    /**
     * Product, which is inserted or updated
     *
     * @var Product
     */
    public $product;
}
