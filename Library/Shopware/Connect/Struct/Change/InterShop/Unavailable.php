<?php
/**
 * This file is part of the Shopware Connect SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Shopware\Connect\Struct\Change\InterShop;

use Shopware\Connect\Struct\Change;
use Shopware\Connect\Struct\Product;

/**
 * Product unavailable change struct
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 * @api
 */
class Unavailable extends Change
{
    /**
     * Current availability of the product in the supplier shop during checkout in merchant shop
     * Used when the availability in the supplier shop is less than the requested in teh checkout process of the merchant shop.
     *
     * @var int
     */
    public $availability = 0;
}
