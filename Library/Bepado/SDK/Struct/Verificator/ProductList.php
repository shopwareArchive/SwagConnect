<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
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
