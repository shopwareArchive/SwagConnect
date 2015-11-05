<?php

namespace Tests\Shopware\Connect\CategoryQuery;

use Enlight_Components_Test_Plugin_TestCase;
use Bepado\SDK\Struct\Product;
use Shopware\Connect\Components\ConnectFactory;

class Sw41QueryTest extends CategoryQueryTest
{
    protected function createQuery()
    {
        $factory = new ConnectFactory();

        if (!$factory->checkMinimumVersion('4.1.0')) {
            $this->markTestSkipped('This tests only run on Shopware 4.1.0 and greater');
        }

        return $factory->getShopware41CategoryQuery();
    }
}
