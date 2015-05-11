<?php

namespace Tests\Shopware\Bepado;

use Bepado\SDK\Struct\Product;

class HelperTest extends BepadoTestHelper
{
    public function testGetDefaultCustomerGroup()
    {
        $group = $this->getHelper()->getDefaultCustomerGroup();
        $this->assertInstanceOf('\Shopware\Models\Customer\Group', $group);
    }

    /**
     * @depends testGetProductById
     */
    public function testGetArticleModelByProduct($product)
    {
        $model = $this->getHelper()->getArticleModelByProduct($product);
        $this->assertNotEmpty($model->getName());
    }

    public function testHasBasketBepadoProductsIsFalse()
    {
        $result = $this->getHelper()->hasBasketBepadoProducts(333);
        $this->assertFalse($result);
    }

    public function testHasBasketBepadoProductsIsTrue()
    {
        // Bootstrap a shop object
        /** @var \Shopware\Models\Shop\Repository $repo */
        $repo = Shopware()->Models()->getRepository('Shopware\Models\Shop\Shop');
        $shop = $repo->getActiveDefault();
        $shop->registerResources(Shopware()->Bootstrap());

        $id = $this->getBepadoProductArticleId();
        /** @var \Shopware\Models\Article\Detail $detail */
        $detail = Shopware()->Models()->getRepository('Shopware\Models\Article\Detail')->findOneBy(array('articleId' => $id, 'kind' => 1));


        // todo@dn: Fix unit test
//        $this->Request()->setMethod('GET');
//        $this->dispatch('/account/login');
//
//
//        // Add bepado product to basket
//        $wasSuccessFul = Shopware()->Modules()->Basket()->sAddArticle($detail->getNumber());
//
//
//        $result = $this->getHelper()->hasBasketBepadoProducts(333);
//        $this->assertFalse($result);
    }

    public function testGetBepadoAttributeByModel()
    {
        $attributes = Shopware()->Models()->getRepository('Shopware\CustomModels\Bepado\Attribute')->findBy(array('articleId' => 14));
        foreach ($attributes as $attribute) {
            Shopware()->Models()->remove($attribute);
        }
        Shopware()->Models()->flush();

        /** @var \Shopware\Models\Article\Article $model */
        $model = Shopware()->Models()->getRepository('Shopware\Models\Article\Article')->find(14);

        $bepadoAttribute = $this->getHelper()->getBepadoAttributeByModel($model);
        $this->assertEmpty($bepadoAttribute);
    }

    public function testGetOrCreateBepadoAttributeByModel()
    {
        /** @var \Shopware\Models\Article\Article $model */
        $model = Shopware()->Models()->getRepository('Shopware\Models\Article\Article')->find(14);

        $bepadoAttribute = $this->getHelper()->getOrCreateBepadoAttributeByModel($model);
        $this->assertNotEmpty($bepadoAttribute->getId());
    }

    public function _testGetImagesById()
    {
        $images = array();
        for ($i=0; $i<10; $i++) {
            $images[] = 'http://lorempixel.com/400/200?'.rand(0,9999);
        }

        /** @var \Shopware\Models\Article\Article $model */
        $model = Shopware()->Models()->find('Shopware\Models\Article\Article', 2);

        $this->getImageImport()->importImagesForArticle(
            $images,
            $model
        );

        $images = $this->callPrivate($this->getHelper(), 'getImagesById', 2);
        $this->assertArrayCount(13, count($images));
    }

    public function testGetCategoriesByProduct()
    {
        $this->markTestSkipped('It fails in travis, but works locally');
        $this->resetBepadoCategoryMappings();
        $this->changeCategoryBepadoMappingForCategoryTo(12, '/bücher'); // 12 == Tees im Demoshop

        $sourceId = $this->getExternalProductSourceId();
        $products = $this->getHelper()->getRemoteProducts(array($sourceId));
        $categories = $this->getHelper()->getCategoriesByProduct($products[0]);

        $this->assertNotEmpty($categories);
    }

    private function resetBepadoCategoryMappings()
    {
        $conn = Shopware()->Db();
        $conn->exec('UPDATE s_categories_attributes SET bepado_import_mapping = NULL, bepado_export_mapping = NULL');
    }
}