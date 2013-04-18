<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * @version $Revision$
 */

namespace Bepado\SDK\ProductHasher;

use Bepado\SDK\ProductHasher;
use Bepado\SDK\Struct;

/**
 * Base class for product hasher implementations
 *
 * @version $Revision$
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
