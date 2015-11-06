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
