<?php

namespace Tests\ShopwarePlugins\Connect;

use Shopware\Connect\Struct\Product;

class HelperTest extends ConnectTestHelper
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

    public function testHasBasketConnectProductsIsFalse()
    {
        $result = $this->getHelper()->hasBasketConnectProducts(333);
        $this->assertFalse($result);
    }

    public function testHasBasketConnectProductsIsTrue()
    {
        // Bootstrap a shop object
        /** @var \Shopware\Models\Shop\Repository $repo */
        $repo = Shopware()->Models()->getRepository('Shopware\Models\Shop\Shop');
        $shop = $repo->getActiveDefault();
        $shop->registerResources(Shopware()->Bootstrap());

        // todo@sb: Fix unit test
//        $id = $this->getConnectProductArticleId();
//        /** @var \Shopware\Models\Article\Detail $detail */
//        $detail = Shopware()->Models()->getRepository('Shopware\Models\Article\Detail')->findOneBy(array('articleId' => $id, 'kind' => 1));



//        $this->Request()->setMethod('GET');
//        $this->dispatch('/account/login');
//
//
//        // Add connect product to basket
//        $wasSuccessFul = Shopware()->Modules()->Basket()->sAddArticle($detail->getNumber());
//
//
//        $result = $this->getHelper()->hasBasketConnectProducts(333);
//        $this->assertFalse($result);
    }

    public function testGetConnectAttributeByModel()
    {
        $attributes = Shopware()->Models()->getRepository('Shopware\CustomModels\Connect\Attribute')->findBy(array('articleId' => 14));
        foreach ($attributes as $attribute) {
            Shopware()->Models()->remove($attribute);
        }
        Shopware()->Models()->flush();

        /** @var \Shopware\Models\Article\Article $model */
        $model = Shopware()->Models()->getRepository('Shopware\Models\Article\Article')->find(14);

        $connectAttribute = $this->getHelper()->getConnectAttributeByModel($model);
        $this->assertEmpty($connectAttribute);
    }

    public function testGetOrCreateConnectAttributeByModel()
    {
        /** @var \Shopware\Models\Article\Article $model */
        $model = Shopware()->Models()->getRepository('Shopware\Models\Article\Article')->find(14);

        $connectAttribute = $this->getHelper()->getOrCreateConnectAttributeByModel($model);
        $this->assertNotEmpty($connectAttribute->getId());
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
        $this->resetConnectCategoryMappings();
        $this->changeCategoryConnectMappingForCategoryTo(12, '/bücher'); // 12 == Tees im Demoshop

        $sourceId = $this->getExternalProductSourceId();
        $products = $this->getHelper()->getRemoteProducts(array($sourceId));
        $categories = $this->getHelper()->getCategoriesByProduct($products[0]);

        $this->assertNotEmpty($categories);
    }

    private function resetConnectCategoryMappings()
    {
        $conn = Shopware()->Db();
        $conn->exec('UPDATE s_categories_attributes SET connect_import_mapping = NULL, connect_export_mapping = NULL');
    }
}