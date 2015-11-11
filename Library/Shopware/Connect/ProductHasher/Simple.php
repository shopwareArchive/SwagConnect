<?php
/**
 * This file is part of the Shopware Connect SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Shopware\Connect\ProductHasher;

use Shopware\Connect\ProductHasher;
use Shopware\Connect\Struct;

/**
 * Base class for product hasher implementations
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */
class Simple extends ProductHasher
{
    /**
     * Get hash for product
     *
     * @param \Shopware\Connect\Struct\Product $product
     * @return string
     */
    public function hash(Struct\Product $product)
    {
        return md5(serialize($product));
    }
}
