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

    public function testGetProductDescriptionField()
    {
        $allowedFields = array('a.descriptionLong', 'a.description', 'attribute.bepadoProductDescription');

        $field = $this->getHelper()->getProductDescriptionField();
        if (!in_array($field, $allowedFields)) {
            $this->fail("Failed asserting that {$field} is a valid field");
        }
    }

    public function testGetArticleModelById()
    {
        $model = $this->getHelper()->getArticleModelById(2);
        $this->assertEquals(2, $model->getId());
    }

    public function testGetCategoryModelById()
    {
        $model = $this->getHelper()->getCategoryModelById(14);
        $this->assertEquals(14, $model->getId());
    }

    public function testGetProductByRowData()
    {
        $product = $this->getHelper()->getProductByRowData(array(
            'sourceId' => 1,
            'ean' => 'asdf',
            'url' => 'http://www.example.org',
            'title' => 'Example Article',
            'altDescription' => 'alt',
            'shortDescription' => 'short desc',
            'longDescription' => 'long',
            'vendor' => 'Shopware',
            'price' => 33.20,
            'purchasePrice' => 20.00,
            'fixedPrice' => 1,
            'weight' => 23,
            'categories' => serialize(array('/bücher'))
        ));

        $this->assertInstanceOf('\Bepado\SDK\Struct\Product', $product);

        // Check array unserializeation
        $this->assertArrayCount(1, $product->categories);

        // Check alt-description switch
        $this->assertEquals('alt', $product->longDescription);

        // Check attributes
        $this->assertEquals(23, $product->attributes['weight']);
    }

    public function testGetProductById()
    {
        $id = $this->getBepadoProductArticleId();

        $product = $this->getHelper()->getProductById($id);
        $this->assertInstanceOf('\Bepado\SDK\Struct\Product', $product);

        return $product;
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

        $id =$this->getBepadoProductArticleId();
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

    public function testGetImagesById()
    {
        $images = $this->getHelper()->getImagesById(2);
        $this->assertArrayCount(3, $images);
    }



    /**
     * @return string
     */
    public function getBepadoProductArticleId()
    {
        $id = Shopware()->Db()->fetchOne(
            'SELECT article_id FROM s_plugin_bepado_items WHERE source_id IS NOT NULL LIMIT 1'
        );
        return $id;
    }
}