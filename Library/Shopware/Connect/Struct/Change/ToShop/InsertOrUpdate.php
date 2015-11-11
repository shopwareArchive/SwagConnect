<?php
/**
 * This file is part of the Shopware Connect SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Shopware\Connect\Struct\Change\ToShop;

use Shopware\Connect\Struct\Change;
use Shopware\Connect\Struct\Product;

/**
 * Insert change struct
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
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
