<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Bepado\SDK\Struct\Change\ToShop;

use Bepado\SDK\Struct\Change;

/**
 * Represents a change in purchase price.
 */
class Update extends Change
{
    /**
     * @var \Bepado\SDK\Struct\ProductUpdate
     */
    public $product;
}
