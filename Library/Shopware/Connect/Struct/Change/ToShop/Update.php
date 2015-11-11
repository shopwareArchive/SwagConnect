<?php
/**
 * This file is part of the Shopware Connect SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Shopware\Connect\Struct\Change\ToShop;

use Shopware\Connect\Struct\Change;

/**
 * Represents a change in purchase price.
 */
class Update extends Change
{
    /**
     * @var \Shopware\Connect\Struct\ProductUpdate
     */
    public $product;
}
