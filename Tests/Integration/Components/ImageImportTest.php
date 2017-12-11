<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Tests\Integration\Components;

use ShopwarePlugins\Connect\Components\ConfigFactory;
use ShopwarePlugins\Connect\Components\Logger;
use Shopware\Models\Article\Supplier;
use Shopware\Models\Article\Image;
use Shopware\Models\Media\Media;
use ShopwarePlugins\Connect\Components\ImageImport;
use ShopwarePlugins\Connect\Tests\DatabaseTestCaseTrait;
use ShopwarePlugins\Connect\Tests\ProductBuilderTrait;

class ImageImportTest extends \PHPUnit_Framework_TestCase
{
    use DatabaseTestCaseTrait;
    use ProductBuilderTrait;

    private $manager;
    /** @var ImageImport */
    private $imageImport;

    public function SetUp()
    {
        $this->manager = Shopware()->Models();
        $this->imageImport = new ImageImport(
            Shopware()->Models(),
            Shopware()->Plugins()->Backend()->SwagConnect()->getHelper(),
            Shopware()->Container()->get('thumbnail_manager'),
            new Logger(Shopware()->Db())
        );
    }

    public function testGetProductsNeedingImageImport()
    {
        $result = $this->imageImport->getProductsNeedingImageImport();

        $this->assertNotEmpty($result);
    }

    public function testHasArticleMainImage()
    {
        $result = $this->imageImport->hasArticleMainImage(2);

        $this->assertTrue($result);
    }

    public function testImportImagesForArticle()
    {
        $images = [];
        for ($i=0; $i<3; ++$i) {
            $images[] = $this->imageProviderUrl . '?' . $i;
        }

        /** @var \Shopware\Models\Article\Article $model */
        $model = $this->manager->find('Shopware\Models\Article\Article', 2);
        $model->getImages()->clear();
        $this->imageImport->importImagesForArticle($images, $model);

        // reload article model after image import otherwise model contains only old images
        $model = $this->manager->find('Shopware\Models\Article\Article', 2);
        $this->assertEquals(3, $model->getImages()->count());
    }

    public function testImportImagesForArticleWithMissingMediaForImage()
    {
        $images = [];
        for ($i=0; $i<3; ++$i) {
            $images[] = $this->imageProviderUrl . '?' . $i;
        }

        /** @var \Shopware\Models\Article\Article $model */
        $model = $this->manager->find('Shopware\Models\Article\Article', 2);
        $media = $model->getImages()[0]->getMedia();
        $model->getImages()->clear();
        $image = new Image();
        $image->setMedia($media);
        $model->getImages()->add($image);
        $this->manager->persist($model);
        $this->manager->persist($media);
        $this->manager->flush();

        $this->manager->getConnection()->executeQuery(
            'DELETE FROM s_media WHERE id = ?',
            [$media->getId()]
        );
        $mediaId = $this->manager->getConnection()->fetchColumn(
            'SELECT media_id FROM s_articles_img WHERE id = ?',
            [$image->getId()]
        );

        //assert that the media id is not deleted -> we want to test exactly this case
        //see https://jira.shopware.com/browse/CON-4938
        $this->assertTrue(is_numeric($mediaId));

        $this->imageImport->importImagesForArticle($images, $model);

        // reload article model after image import otherwise model contains only old images
        $model = $this->manager->find('Shopware\Models\Article\Article', 2);
        $this->assertEquals(3, $model->getImages()->count());
    }

    public function testImportDifferentImagesForEachVariant()
    {
        $articleImages = [
            $this->imageProviderUrl . '?0'
        ];
        $variantImages = [];
        for ($i=1; $i<10; ++$i) {
            $variantImages[] = $this->imageProviderUrl . '?' . $i;
        }

        $expectedVariantImages = $variantImages;
        /** @var \Shopware\Models\Article\Article $article */
        $article = $this->manager->find('Shopware\Models\Article\Article', 2);
        $article->getImages()->clear();

        $this->imageImport->importImagesForArticle($articleImages, $article);

        /** @var \Shopware\Models\Article\Detail $detail */
        foreach ($article->getDetails() as $detail) {
            //clear all imported images before test
            foreach ($detail->getImages() as $image) {
                $this->manager->remove($image);
            }
            $this->manager->flush();
            $this->imageImport->importImagesForDetail(array_splice($variantImages, 0, 3), $detail);
        }

        // reload article model after image import otherwise model contains only old images
        $article = $this->manager->find('Shopware\Models\Article\Article', 2);
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
        $supplier = $this->manager->find('Shopware\Models\Article\Supplier', 1);
        $supplier->setImage('');

        $this->imageImport->importImageForSupplier($this->imageProviderUrl, $supplier);

        $this->assertNotEmpty($supplier->getImage());
    }

    public function testBatchImport()
    {
        Shopware()->Db()->executeQuery('UPDATE s_plugin_connect_items SET last_update_flag = 0');

        ConfigFactory::getConfigInstance()->setConfig('importImagesOnFirstImport', 0);

        $this->insertOrUpdateProducts(1, true, true);

        $result = $this->imageImport->getProductsNeedingImageImport();
        $articleId = reset($result);
        /** @var \Shopware\Models\Article\Article $article */
        $article = $this->manager->find('Shopware\Models\Article\Article', $articleId);

        $this->assertEmpty($article->getImages());
        $this->imageImport->import(1);

        /** @var \Shopware\Models\Article\Article $article */
        $article = $this->manager->find('Shopware\Models\Article\Article', $articleId);
        $this->assertEquals(2, $article->getImages()->count());
        $this->assertEquals(1, $article->getMainDetail()->getImages()->count());

        $this->assertEmpty($this->imageImport->getProductsNeedingImageImport());
    }

    public function testImportMainImage()
    {
        $this->importFixtures(__DIR__ . '/_fixtures/connect_media.sql');

        $this->imageImport->importMainImage('no_main_img', 14467);

        $result = $this->manager->getConnection()->fetchColumn('SELECT id FROM s_articles_img WHERE articleID = ? AND main = 1 AND parent_id IS NULL',
            [14467]);

        $this->assertEquals(1235, $result);
    }

    public function testHasMainImageChnagedReturnsTrue()
    {
        $this->importFixtures(__DIR__ . '/_fixtures/connect_media.sql');

        $result = $this->imageImport->hasMainImageChanged('no_main_img', 14467);

        $this->assertEquals(true, $result);
    }

    public function testHasMainImageChnagedReturnsFalse()
    {
        $this->importFixtures(__DIR__ . '/_fixtures/connect_media.sql');

        $result = $this->imageImport->hasMainImageChanged('main_img', 14467);

        $this->assertEquals(false, $result);
    }
}
