<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\ShopwarePlugins\Connect;

use Shopware\Models\Article\Supplier;

class ImageImportTest extends ConnectTestHelper
{
    public function testGetProductsNeedingImageImport()
    {
        $ids = $this->insertOrUpdateProducts(10, false, false);

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
        $images = [];
        for ($i=0; $i<10; ++$i) {
            $images[] = self::IMAGE_PROVIDER_URL . '?' . $i;
        }

        /** @var \Shopware\Models\Article\Article $model */
        $model = Shopware()->Models()->find('Shopware\Models\Article\Article', 2);
        $model->getImages()->clear();
        $this->getImageImport()->importImagesForArticle($images, $model);

        // reload article model after image import otherwise model contains only old images
        $model = Shopware()->Models()->find('Shopware\Models\Article\Article', 2);
        $this->assertEquals(10, $model->getImages()->count());
    }

    public function testImportDifferentImagesForEachVariant()
    {
        $articleImages = [
            self::IMAGE_PROVIDER_URL . '?0'
        ];
        $variantImages = [];
        for ($i=1; $i<10; ++$i) {
            $variantImages[] = self::IMAGE_PROVIDER_URL . '?' . $i;
        }

        $expectedVariantImages = $variantImages;
        /** @var \Shopware\Models\Article\Article $article */
        $article = Shopware()->Models()->find('Shopware\Models\Article\Article', 2);
        $article->getImages()->clear();

        $this->getImageImport()->importImagesForArticle($articleImages, $article);

        /** @var \Shopware\Models\Article\Detail $detail */
        foreach ($article->getDetails() as $detail) {
            //clear all imported images before test
            foreach ($detail->getImages() as $image) {
                Shopware()->Models()->remove($image);
            }
            Shopware()->Models()->flush();
            $this->getImageImport()->importImagesForDetail(array_splice($variantImages, 0, 3), $detail);
        }

        // reload article model after image import otherwise model contains only old images
        $article = Shopware()->Models()->find('Shopware\Models\Article\Article', 2);
        // article must contain 1 global and 9 specific variant images
        $this->assertEquals(10, $article->getImages()->count());

        $importedArticleImages = $article->getImages();
        /** @var \Shopware\Models\Article\Image $mainImage */
        $mainImage = $importedArticleImages[0];
        $this->assertEmpty($mainImage->getMappings());
        $media = $mainImage->getMedia();
        $mediaAttribute = $media->getAttribute();

        $this->assertEquals($articleImages[0], $mediaAttribute->getConnectHash());

        unset($importedArticleImages[0]);
        foreach ($importedArticleImages as $image) {
            /** @var \Shopware\Models\Article\Image $image */
            $this->assertNotEmpty($image->getMappings());
            $this->assertEquals(array_shift($expectedVariantImages), $image->getMedia()->getAttribute()->getConnectHash());
        }

        /** @var \Shopware\Models\Article\Detail $detail */
        foreach ($article->getDetails() as $detail) {
            $this->assertEquals(3, $detail->getImages()->count());
        }
    }

    public function testImportImagesForSupplier()
    {
        /* @var Supplier $supplier*/
        $supplier = Shopware()->Models()->find('Shopware\Models\Article\Supplier', 1);
        $supplier->setImage('');

        $this->getImageImport()->importImageForSupplier(self::IMAGE_PROVIDER_URL, $supplier);

        $this->assertNotEmpty($supplier->getImage());
    }

    public function testBatchImport()
    {
        Shopware()->Db()->executeQuery('UPDATE s_plugin_connect_items SET last_update_flag = 0');
        $this->insertOrUpdateProducts(1, true, true);

        $result = $this->getImageImport()->getProductsNeedingImageImport();
        $articleId = reset($result);
        /** @var \Shopware\Models\Article\Article $article */
        $article = Shopware()->Models()->find('Shopware\Models\Article\Article', $articleId);

        $this->assertEmpty($article->getImages());
        $this->getImageImport()->import(1);

        /** @var \Shopware\Models\Article\Article $article */
        $article = Shopware()->Models()->find('Shopware\Models\Article\Article', $articleId);
        $this->assertEquals(2, $article->getImages()->count());
        $this->assertEquals(1, $article->getMainDetail()->getImages()->count());

        $this->assertEmpty($this->getImageImport()->getProductsNeedingImageImport());
    }
}
