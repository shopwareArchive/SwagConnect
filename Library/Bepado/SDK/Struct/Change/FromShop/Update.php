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
 * Update change struct
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
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
}
