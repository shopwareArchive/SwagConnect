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
}
 