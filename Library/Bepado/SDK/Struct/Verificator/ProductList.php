<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * @version 1.1.141
 */

namespace Bepado\SDK\Struct\Verificator;

use Bepado\SDK\Struct\Verificator;
use Bepado\SDK\Struct\VerificatorDispatcher;
use Bepado\SDK\Struct;

class ProductList extends Verificator
{
    public function verify(VerificatorDispatcher $dispatcher, Struct $struct)
    {
        foreach ($struct->products as $product) {
            if (!($product instanceof Struct\Product)) {
                throw new \RuntimeException(
                    "Elements passed to a product list must be instanceof Bepado\SDK\Struct\Product"
                );
            }

            $dispatcher->verify($product);
        }
    }
}
