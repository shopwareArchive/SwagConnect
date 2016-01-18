<?php

namespace Tests\ShopwarePlugins\Connect;

use Shopware\Models\Article\Supplier;

class ImageImportTest extends ConnectTestHelper
{
    public function testGetProductsNeedingImageImport()
    {
        $ids = $this->insertOrUpdateProducts(10, false);

        $result = $this->getImageImport()->getProductsNeedingImageImport();

        $this->assertNotEmpty($result);
    }


    public function testHasArticleMainImage()
    {
        $result = $this->getImageImport()->hasArticleMainImage(2);

        $this->assertTrue($result);
    }

    public function testImportImagesForArticle()
    {
        $images = array();
        for ($i=0; $i<10; $i++) {
            $images[] = 'http://lorempixel.com/400/200?'.$i;
        }

        /** @var \Shopware\Models\Article\Article $model */
        $model = Shopware()->Models()->find('Shopware\Models\Article\Article', 2);
        $model->getImages()->clear();
        $this->getImageImport()->importImagesForArticle($images, $model);

        // reload article model after image import otherwise model contains only old images
        $model = Shopware()->Models()->find('Shopware\Models\Article\Article', 2);
        $this->assertEquals(10, $model->getImages()->count());
    }

    public function testImportImagesForSupplier()
    {
        $imageUrl = 'http://lorempixel.com/400/200';

        /* @var Supplier $supplier*/
        $supplier = Shopware()->Models()->find('Shopware\Models\Article\Supplier', 1);
        $supplier->setImage('');

        $this->getImageImport()->importImageForSupplier($imageUrl, $supplier);

        $this->assertNotEmpty($supplier->getImage());
    }

}