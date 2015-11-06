<?php

namespace Tests\ShopwarePlugins\Connect\CategoryQuery;

use ShopwarePlugins\Connect\Components\ConnectFactory;

class Sw40QueryTest extends CategoryQueryTest
{
    protected function createQuery()
    {
        $factory = new ConnectFactory();

        if ($factory->checkMinimumVersion('4.1.0')) {
            $this->markTestSkipped('This tests only run on Shopware 4.1.0 and greater');
        }

        return $factory->getShopware40CategoryQuery();
    }
}

