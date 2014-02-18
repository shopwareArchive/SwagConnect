<?php

namespace Tests\Shopware\Bepado;

class ImageImportTest extends BepadoTestHelper
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
        $this->getImageImport()->importImagesForArticle($images, $model);

        $this->assertArrayCount(13, $model->getImages());
    }

}