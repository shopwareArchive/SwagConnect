<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Bepado\SDK\ProductHasher;

use Bepado\SDK\ProductHasher;
use Bepado\SDK\Struct;

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
     * @param \Bepado\SDK\Struct\Product $product
     * @return string
     */
    public function hash(Struct\Product $product)
    {
        return md5(serialize($product));
    }
}
