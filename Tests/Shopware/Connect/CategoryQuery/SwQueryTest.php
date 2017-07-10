<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\ShopwarePlugins\Connect\CategoryQuery;

use ShopwarePlugins\Connect\Components\CategoryQuery\RelevanceSorter;
use ShopwarePlugins\Connect\Components\CategoryQuery\SwQuery;
use Tests\ShopwarePlugins\Connect\ConnectTestHelper;

class SwQueryTest extends ConnectTestHelper
{
    protected function createQuery()
    {
        return new SwQuery(Shopware()->Models(), new RelevanceSorter());
    }

    /**
    * @param string $name
    * @param string $key
    * @dataProvider provideCategoryNames
    */
    public function testNormalizeCategoryName($name, $key)
    {
        $this->assertTrue(true);
        $categoryQuery = $this->createQuery();

        $this->assertEquals(
            $key,
            $categoryQuery->normalizeCategory($name)
        );
    }

    public function provideCategoryNames()
    {
        return [
            [
                'Zubehör für Videospielkonsolen',
                '/zubehör_für_videospielkonsolen',
            ],
            [
                'Hobby & Kunst',
                '/hobby_kunst',
            ],
            [
                'Sofa-Zubehör',
                '/sofa_zubehör',
            ],
            [
                'Forst- & Holzwirtschaft',
                '/forst_holzwirtschaft',
            ],
            [
                'VariantenKat1',
                '/variantenkat1',
            ],
            [
                'VariantenKat2',
                '/variantenkat2',
            ],
            [
                'VariantenKat3',
                '/variantenkat3',
            ],
            [
                '4VariantenKat',
                '/4variantenkat',
            ],
            [
                '5VariantenKat',
                '/5variantenkat',
            ],
        ];
    }
}
