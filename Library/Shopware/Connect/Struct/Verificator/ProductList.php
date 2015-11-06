<?php
/**
 * This file is part of the Shopware Connect SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Shopware\Connect\Struct\Verificator;

use Shopware\Connect\Struct\Verificator;
use Shopware\Connect\Struct\VerificatorDispatcher;
use Shopware\Connect\Struct;

class ProductList extends Verificator
{
    protected function verifyDefault(VerificatorDispatcher $dispatcher, Struct $struct)
    {
        foreach ($struct->products as $product) {
            if (!($product instanceof Struct\Product)) {
                throw new \Shopware\Connect\Exception\VerificationFailedException(
                    "Elements passed to a product list must be instanceof Shopware\Connect\Struct\Product"
                );
            }

            $dispatcher->verify($product);
        }
    }
}
