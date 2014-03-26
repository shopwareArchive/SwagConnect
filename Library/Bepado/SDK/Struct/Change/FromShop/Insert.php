<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Bepado\SDK\Struct\Change\FromShop;

use Bepado\SDK\Struct\Change;
use Bepado\SDK\Struct\Product;

/**
 * Insert change struct
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
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
