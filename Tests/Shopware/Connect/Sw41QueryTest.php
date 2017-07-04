<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\ShopwarePlugins\Connect\CategoryQuery;

use Shopware\Connect\Struct\Product;
use ShopwarePlugins\Connect\Components\ConnectFactory;
use Tests\ShopwarePlugins\Connect\ConnectTestHelper;

class Sw41QueryTest extends ConnectTestHelper
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

    public function testGetCategoriesByProduct()
    {
        $this->resetConnectCategoryMappings();
        $this->changeCategoryConnectMappingForCategoryTo(12, '/bücher'); // 12 == Tees im Demoshop

        $query = $this->createQuery();

        $product = new Product();
        $product->categories = ['/bücher'];

        $categories = $query->getCategoriesByProduct($product);

        $this->assertInternalType('array', $categories);
        $this->assertCount(1, $categories);
        $this->assertEquals('Tees', $categories[0]->getName());
    }

    public function testGetConnectCategoryForProduct()
    {
        $this->resetConnectCategoryMappings();
        $this->changeCategoryConnectMappingForCategoryTo(12, '/bücher'); // 12 == Tees im Demoshop
        $this->changeCategoryConnectMappingForCategoryTo(12, '/bücher'); // 12 == Tees im Demoshop

        $query = $this->createQuery();

        $categories = $query->getConnectCategoryForProduct(21); // 21 = Jasmine Tee im Demoshop

        $expectedCategories = [
            '/deutsch/genusswelten/tees_und_zubehör/tees' => 'Tees',
            '/deutsch/genusswelten/tees_und_zubehör' => 'Tees und Zubehör',
            '/deutsch/genusswelten' => 'Genusswelten',
            '/deutsch' => 'Deutsch',
            '/english/worlds_of_indulgence/teas_and_accessories/teas' => 'Teas',
            '/english/worlds_of_indulgence/teas_and_accessories' => 'Teas and Accessories',
            '/english/worlds_of_indulgence' => 'Worlds of indulgence',
            '/english' => 'English',
        ];

        $this->assertEquals($expectedCategories, $categories);
    }

    private function resetConnectCategoryMappings()
    {
        $conn = Shopware()->Db();
        $conn->exec('UPDATE s_categories_attributes SET connect_import_mapping = NULL, connect_export_mapping = NULL');
    }
}
