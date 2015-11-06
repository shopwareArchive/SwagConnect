<?php
/**
 * This file is part of the Shopware Connect SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Shopware\Connect\Struct\Change\FromShop;

use Shopware\Connect\Struct\Change;
use Shopware\Connect\Struct\Product;

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
