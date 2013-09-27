<?php

namespace Shopware\Bepado\CategoryQuery;

use Enlight_Components_Test_Plugin_TestCase;
use Bepado\SDK\Struct\Product;
use Shopware\Bepado\BepadoFactory;

class Sw41QueryTest extends CategoryQueryTest
{
    protected function createQuery()
    {
        Shopware()->Bootstrap()->getResource('BepadoSDK');

        $factory = new BepadoFactory();

        if (!$factory->isMinorVersion('4.1')) {
            $this->markTestSkipped('This tests only run on Shopware 4.1.0 and greater');
        }

        return $factory->getShopware41CategoryQuery();
    }
}
