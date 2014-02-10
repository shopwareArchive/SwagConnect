<?php
/**
 * This file is part of the Bepado Common Component.
 *
 * @version $Revision$
 */

namespace Bepado\Common\Struct\Product;

use Bepado\Common\Struct\Product;

/**
 *
 */
class MasterProduct extends Product
{
    /**
     * Identifier of the original shop product which created this master.
     *
     * @var string
     */
    public $originalId;

    /**
     * Number of available source/shop products.
     *
     * @var integer
     */
    public $sourceCount;

    /**
     * Aggregated shop products
     *
     * @var ShopProduct[]
     */
    public $shopProducts = array();
}
