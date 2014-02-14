<?php

namespace Tests\Shopware\Bepado;

class ImageImportTest extends BepadoTestHelper
{
    public function testGetProductsNeedingImageImport()
    {
        $this->dispatchRpcCall('products', 'toShop', array(
            array(
                new \Bepado\SDK\Struct\Change\ToShop\InsertOrUpdate(array(
                    'product' => new \Bepado\SDK\Struct\Product(array(
                        'shopId' => 3,
                        'revisionId' => time(),
                        'sourceId' => rand(111, 888888),
                        'ean' => '1234',
                        'url' => 'http://shopware.de',
                        'title' => 'Bepado Test-Produkt',
                        'shortDescription' => 'Ein Produkt aus Bepado',
                        'longDescription' => 'Ein Produkt aus Bepado',
                        'vendor' => 'Bepado',
                        'price' => 9.99,
                        'purchasePrice' => 6.99,
                        'availability' => 100,
                        'images' => array('http://lorempixel.com/400/200'),
                        'categories' => array('/bücher'),
                    )),
                    'revision' => time(),
                ))
            )
        ));

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