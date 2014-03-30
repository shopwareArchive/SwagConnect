<?php

namespace Tests\Shopware\Bepado\CategoryQuery;

use Bepado\SDK\Struct\Product;
use Tests\Shopware\Bepado\BepadoTestHelper;

abstract class CategoryQueryTest extends BepadoTestHelper
{
    abstract protected function createQuery();

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
        $conn->exec('UPDATE s_categories_attributes SET bepado_import_mapping = NULL');
        $conn->exec('UPDATE s_categories_attributes SET bepado_export_mapping = NULL');
    }
}
