<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * @version 1.1.133
 */

namespace Bepado\SDK\Struct;

use Bepado\SDK\Struct;

/**
 * A set of products that will be part of an order.
 *
 * @version 1.1.133
 * @api
 */
class ProductList extends Struct
{
    /**
     * @var Product[]
     */
    public $products;
}
