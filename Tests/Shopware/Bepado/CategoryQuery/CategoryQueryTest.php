<?php

namespace Tests\Shopware\Bepado\CategoryQuery;

use Bepado\SDK\Struct\Product;
use Tests\Shopware\Bepado\BepadoTestHelper;

abstract class CategoryQueryTest extends BepadoTestHelper
{
    abstract protected function createQuery();

    public function testGetBepadoCategoryForProduct()
    {
        $this->resetBepadoCategoryMappings();
        $this->changeCategoryBepadoMappingForCategoryTo(12, '/bücher'); // 12 == Tees im Demoshop
        $this->changeCategoryBepadoMappingForCategoryTo(12, '/bücher'); // 12 == Tees im Demoshop

        $query = $this->createQuery();

        $categories = $query->getBepadoCategoryForProduct(21); // 21 = Jasmine Tee im Demoshop

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
        $this->resetBepadoCategoryMappings();
        $this->changeCategoryBepadoMappingForCategoryTo(12, '/bücher'); // 12 == Tees im Demoshop

        $query = $this->createQuery();

        $product = new Product();
        $product->categories = array('/bücher');

        $categories = $query->getCategoriesByProduct($product);

        $this->assertInternalType('array', $categories);
        $this->assertCount(1, $categories);
        $this->assertEquals('Tees', $categories[0]->getName());
    }

    private function resetBepadoCategoryMappings()
    {
        $conn = Shopware()->Db();
        $conn->exec('UPDATE s_categories_attributes SET bepado_import_mapping = NULL, bepado_export_mapping = NULL');
    }
}
