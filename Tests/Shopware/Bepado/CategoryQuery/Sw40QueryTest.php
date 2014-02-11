<?php

namespace Shopware\Bepado\Components\CategoryQuery;

use Shopware\Bepado\Components\BepadoFactory;

class Sw40QueryTest extends CategoryQueryTest
{
    protected function createQuery()
    {
        $factory = new BepadoFactory();

        if ($factory->checkMinimumVersion('4.1.0')) {
            $this->markTestSkipped('This tests only run on Shopware 4.1.0 and greater');
        }

        return $factory->getShopware40CategoryQuery();
    }
}

