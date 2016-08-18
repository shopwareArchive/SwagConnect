<?php

namespace Tests\ShopwarePlugins\Connect\CategoryQuery;

use Enlight_Components_Test_Plugin_TestCase;
use Shopware\Connect\Struct\Product;
use ShopwarePlugins\Connect\Components\ConnectFactory;

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

    /**
    * @param string $name
    * @param string $key
    * @dataProvider provideCategoryNames
    */
    public function testNormalizeCategoryName($name, $key)
    {
        $categoryQuery = $this->createQuery();

        $this->assertEquals(
            $key,
            $categoryQuery->normalizeCategory($name)
        );
    }

    public function provideCategoryNames()
    {
        return array(
            array(
                'Zubehör für Videospielkonsolen',
                '/zubehör_für_videospielkonsolen',
            ),
            array(
                'Hobby & Kunst',
                '/hobby_kunst',
            ),
            array(
                'Sofa-Zubehör',
                '/sofa_zubehör',
            ),
            array(
                'Forst- & Holzwirtschaft',
                '/forst_holzwirtschaft',
            ),
            array(
                'VariantenKat1',
                '/variantenkat1',
            ),
            array(
                'VariantenKat2',
                '/variantenkat2',
            ),
            array(
                'VariantenKat3',
                '/variantenkat3',
            ),
            array(
                '4VariantenKat',
                '/4variantenkat',
            ),
            array(
                '5VariantenKat',
                '/5variantenkat',
            ),
        );
    }
}
