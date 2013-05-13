<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * @version $Revision$
 */

namespace Bepado\SDK\Struct;

use Bepado\SDK\Struct;

/**
 * A set of products that will be part of an order.
 *
 * @version $Revision$
 * @api
 */
class ProductList extends Struct
{
    /**
     * @var Product[]
     */
    public $products;
}
