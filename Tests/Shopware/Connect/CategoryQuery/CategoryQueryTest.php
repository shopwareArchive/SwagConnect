<?php

namespace Tests\ShopwarePlugins\Connect\CategoryQuery;

use Shopware\Connect\Struct\Product;
use Tests\ShopwarePlugins\Connect\ConnectTestHelper;

abstract class CategoryQueryTest extends ConnectTestHelper
{
    abstract protected function createQuery();

    public function testGetConnectCategoryForProduct()
    {
        $this->resetConnectCategoryMappings();
        $this->changeCategoryConnectMappingForCategoryTo(12, '/bücher'); // 12 == Tees im Demoshop
        $this->changeCategoryConnectMappingForCategoryTo(12, '/bücher'); // 12 == Tees im Demoshop

        $query = $this->createQuery();

        $categories = $query->getConnectCategoryForProduct(21); // 21 = Jasmine Tee im Demoshop

        $expectedCategories = array(
            '/deutsch/genusswelten/tees_und_zubehör/tees' => 'Tees',
            '/deutsch/genusswelten/tees_und_zubehör' => 'Tees und Zubehör',
            '/deutsch/genusswelten' => 'Genusswelten',
            '/deutsch' => 'Deutsch',
            '/english/worlds_of_indulgence/teas_and_accessories/teas' => 'Teas',
            '/english/worlds_of_indulgence/teas_and_accessories' => 'Teas and Accessories',
            '/english/worlds_of_indulgence' => 'Worlds of indulgence',
            '/english' => 'English',
        );

        $this->assertEquals($expectedCategories, $categories);
    }

    public function testGetCategoriesByProduct()
    {
        $this->resetConnectCategoryMappings();
        $this->changeCategoryConnectMappingForCategoryTo(12, '/bücher'); // 12 == Tees im Demoshop

        $query = $this->createQuery();

        $product = new Product();
        $product->categories = array('/bücher');

        $categories = $query->getCategoriesByProduct($product);

        $this->assertInternalType('array', $categories);
        $this->assertCount(1, $categories);
        $this->assertEquals('Tees', $categories[0]->getName());
    }

    private function resetConnectCategoryMappings()
    {
        $conn = Shopware()->Db();
        $conn->exec('UPDATE s_categories_attributes SET connect_import_mapping = NULL, connect_export_mapping = NULL');
    }
}
