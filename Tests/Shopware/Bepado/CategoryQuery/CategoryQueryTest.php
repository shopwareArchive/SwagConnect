<?php

namespace Shopware\Bepado\CategoryQuery;

use Enlight_Components_Test_Plugin_TestCase;
use Bepado\SDK\Struct\Product;
use Shopware\Bepado\BepadoFactory;

abstract class CategoryQueryTest extends Enlight_Components_Test_Plugin_TestCase
{
    abstract protected function createQuery();

    public function testGetRowProductCategoriesById()
    {
        $this->resetBepadoCategoryMappings();
        $this->changeCategoryBepadoMappingForCategoryTo(12, '/bücher'); // 12 == Tees im Demoshop
        $this->changeCategoryBepadoMappingForCategoryTo(12, '/bücher'); // 12 == Tees im Demoshop

        $query = $this->createQuery();

        $categories = $query->getRowProductCategoriesById(21); // 21 = Jasmine Tee im Demoshop

        $this->assertEquals(array('/bücher'), $categories);
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
        $conn->exec('UPDATE s_categories_attributes SET bepado_mapping = NULL');
    }

    private function changeCategoryBepadoMappingForCategoryTo($categoryId, $mapping)
    {
        $modelManager = Shopware()->Models();
        $categoryRepository = $modelManager->getRepository('Shopware\Models\Category\Category');
        $category = $categoryRepository->find($categoryId);

        if (!$category) {
            $this->fail('Could not find category with ID ' . $categoryId);
        }

        $attribute = $category->getAttribute() ?: new \Shopware\Models\Attribute\Category();
        $attribute->setBepadoMapping($mapping);
        $category->setAttribute($attribute);

        $modelManager->flush();
    }
}
