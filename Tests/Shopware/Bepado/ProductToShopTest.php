<?php

namespace Tests\Shopware\Bepado;


use Bepado\SDK\Struct\Product;
use Shopware\Bepado\Components\Config;
use Shopware\Bepado\Components\ProductToShop;
use Shopware\Bepado\Components\VariantConfigurator;

class ProductToShopTest extends BepadoTestHelper
{
    /** @var  \Shopware\Bepado\Components\ProductToShop */
    private $productToShop;

    private $modelManager;

    public function setUp()
    {
        $this->modelManager = Shopware()->Models();
        $this->productToShop = new ProductToShop(
            $this->getHelper(),
            $this->modelManager,
            $this->getImageImport(),
            new Config($this->modelManager),
            new VariantConfigurator($this->modelManager)
        );
    }

    public function testInsertArticle()
    {
        $product = $this->getProduct();
        $this->productToShop->insertOrUpdate($product);

        $articlesCount = Shopware()->Db()->query(
            'SELECT COUNT(s_articles.id)
              FROM s_plugin_bepado_items
              LEFT JOIN s_articles ON (s_plugin_bepado_items.article_id = s_articles.id)
              WHERE s_plugin_bepado_items.source_id = :sourceId',
            array('sourceId' => $product->sourceId)
        )->fetchColumn();

        $this->assertEquals(1, $articlesCount);
    }

    public function testInsertVariants()
    {
        $variants = $this->getVariants();

        foreach ($variants as $variant) {
            $this->productToShop->insertOrUpdate($variant);
        }

        $bepadoAttribute = $this->modelManager
            ->getRepository('Shopware\CustomModels\Bepado\Attribute')
            ->findOneBy(array('sourceId' => $variants[0]->sourceId));
        $article = $bepadoAttribute->getArticle();
        // check articles details count
        $this->assertEquals(4, count($article->getDetails()));
        // check configurator set
        $this->assertNotNull($article->getConfiguratorSet());
        // check configurator group
        $group = $this->modelManager
            ->getRepository('Shopware\Models\Article\Configurator\Group')
            ->findOneBy(array('name' => 'size'));
        $this->assertNotNull($group);
        // check group options
        $optionValues = array('S', 'M', 'L', 'XL');
        $this->assertEquals(4, count($optionValues));
        foreach ($group->getOptions() as $option) {
            $this->assertTrue(in_array($option->getName(), $optionValues));
        }
        // check configuration set options
        $this->assertEquals(4, count($article->getConfiguratorSet()->getOptions()));
        foreach ($article->getConfiguratorSet()->getOptions() as $option) {
            $this->assertTrue(in_array($option->getName(), $optionValues));
        }
    }

    public function testUpdateVariant()
    {
        $variants = $this->getVariants();
        // insert variants
        foreach ($variants as $variant) {
            $this->productToShop->insertOrUpdate($variant);
        }

        $newTitle = 'Massimport#updateVariant' . rand(1, 10000000);
        $newPrice = 22.48;
        $newPurchasePrice = 8.48;
        $newLongDesc = 'Updated bepado variant - long description';
        $newShortDesc = 'Updated bepado variant - short description';
        $newVat = 0.07;
        $variants[1]->title = $newTitle;
        $variants[1]->price = $newPrice;
        $variants[1]->purchasePrice = $newPurchasePrice;
        $variants[1]->longDescription = $newLongDesc;
        $variants[1]->shortDescription = $newShortDesc;
        $variants[1]->images[] = 'http://lorempixel.com/400/200?' . $variants[1]->sourceId;
        $variants[1]->vat = $newVat;

        $this->productToShop->insertOrUpdate($variants[1]);

        /** @var \Shopware\CustomModels\Bepado\Attribute $bepadoAttribute */
        $bepadoAttribute = $this->modelManager
            ->getRepository('Shopware\CustomModels\Bepado\Attribute')
            ->findOneBy(array('sourceId' => $variants[1]->sourceId));
        $this->assertEquals($newTitle, $bepadoAttribute->getArticle()->getName());
        $this->assertEquals($newLongDesc, $bepadoAttribute->getArticle()->getDescriptionLong());
        $this->assertEquals($newShortDesc, $bepadoAttribute->getArticle()->getDescription());
        /** @var \Shopware\Models\Article\Price[] $prices */
        $prices = $bepadoAttribute->getArticleDetail()->getPrices();

        $this->assertEquals($newPrice, $prices[0]->getPrice());
        $this->assertEquals($newPurchasePrice, $prices[0]->getBasePrice());
        $this->assertEquals(2, count($bepadoAttribute->getArticle()->getImages()));
        $this->assertEquals(7.00, $bepadoAttribute->getArticle()->getTax()->getTax());
    }

    public function testImportWithoutTitle()
    {
        $product = new Product();
        $this->assertEmpty($this->productToShop->insertOrUpdate($product));
    }

    public function testImportWithoutVendor()
    {
        $product = new Product();
        $this->assertEmpty($this->productToShop->insertOrUpdate($product));
    }

    public function testDelete()
    {
        $variants = $this->getVariants();
        // insert variants
        foreach ($variants as $variant) {
            $this->productToShop->insertOrUpdate($variant);
        }

        // test delete only one variant
        $this->productToShop->delete($variants[1]->shopId, $variants[1]->sourceId);

        $bepadoAttribute = $this->modelManager
            ->getRepository('Shopware\CustomModels\Bepado\Attribute')
            ->findOneBy(array('sourceId' => $variants[2]->sourceId));

        $article = $bepadoAttribute->getArticle();
        // check articles details count
        $this->assertEquals(3, count($article->getDetails()));

        // test delete article - main article variant
        $this->productToShop->delete($variants[0]->shopId, $variants[0]->sourceId);

        $articlesCount = Shopware()->Db()->query(
            'SELECT COUNT(s_articles.id)
              FROM s_plugin_bepado_items
              LEFT JOIN s_articles ON (s_plugin_bepado_items.article_id = s_articles.id)
              WHERE s_plugin_bepado_items.source_id = :sourceId',
            array('sourceId' => $variants[0]->sourceId)
        )->fetchColumn();

        $this->assertEquals(0, $articlesCount);

        $attributesCount = Shopware()->Db()->query(
            'SELECT COUNT(s_plugin_bepado_items.id)
              FROM s_plugin_bepado_items
              WHERE s_plugin_bepado_items.article_id = :articleId',
            array('articleId' => $article->getId())
        )->fetchColumn();

        $this->assertEquals(0, $attributesCount);
    }
}
 