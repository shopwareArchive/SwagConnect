<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Tests\Integration\Components;

use Shopware\Components\Model\ModelManager;
use Shopware\Connect\Struct\Product;
use Shopware\Models\Article\Detail;
use ShopwarePlugins\Connect\Components\ConnectFactory;
use ShopwarePlugins\Connect\Components\Gateway\ProductTranslationsGateway\PdoProductTranslationsGateway;
use ShopwarePlugins\Connect\Components\Helper;
use ShopwarePlugins\Connect\Components\VariantConfigurator;
use ShopwarePlugins\Connect\Tests\KernelTestCaseTrait;
use ShopwarePlugins\Connect\Tests\ProductBuilderTrait;

class VariantConfiguratorTest extends \PHPUnit_Framework_TestCase
{
    use KernelTestCaseTrait;

    use ProductBuilderTrait;

    /**
     * @var ModelManager
     */
    private $manager;

    /**
     * @var \Enlight_Components_Db_Adapter_Pdo_Mysql
     */
    private $db;

    /**
     * @var Helper
     */
    private $helper;

    private function getVariantConfigurator()
    {
        return new VariantConfigurator(
            $this->getManager(),
            new PdoProductTranslationsGateway($this->getDb())
        );
    }

    /**
     * @return Helper
     */
    private function getHelper()
    {
        $connectFactory = new ConnectFactory();

        return $connectFactory->getHelper();
    }

    /**
     * @return \Enlight_Components_Db_Adapter_Pdo_Mysql
     */
    private function getDb()
    {
        return Shopware()->Db();
    }

    /**
     * @return ModelManager
     */
    private function getManager()
    {
        return Shopware()->Models();
    }

    public function test_configure_new_variant()
    {
        //delete the configurator group to test that its gets correctly recreated
        $this->getDb()->exec("DELETE FROM s_article_configurator_groups WHERE name = 'Farbe'");

        $variants = $this->getVariants();
        $mainVariant = $variants[0];

        $model = $this->getHelper()->createProductModel($mainVariant);

        $detail = new Detail();
        $detail->setActive($model->getActive());
        $this->getManager()->persist($detail);
        $detail->setArticle($model);
        $model->getDetails()->add($detail);

        $this->getVariantConfigurator()->configureVariantAttributes($mainVariant, $detail);

        $configuratorSet = $model->getConfiguratorSet();

        $this->assertNotNull($configuratorSet);

        $this->assertEquals('Set-' . $model->getName(), $configuratorSet->getName());
        $this->assertEquals(false, $configuratorSet->getPublic());
        $this->assertEquals($mainVariant->configuratorSetType, $configuratorSet->getType());

        $groups = $configuratorSet->getGroups();

        $this->assertEquals(1, $groups->count());
        $this->assertEquals('Farbe', $groups[0]->getName());

        $options = $configuratorSet->getOptions();

        $this->assertEquals(1, $options->count());
        $this->assertEquals($options[0]->getName(), $mainVariant->variant['Farbe']);
    }

    public function test_configure_serveral_variant_products()
    {
        $variants = $this->getVariants();
        $variantConfigurator = $this->getVariantConfigurator();

        $colorOptions = array_map(
            function (Product $product) {
                return $product->variant['Farbe'];
            },
            $variants);

        $mainVariant = $this->getHelper()->createProductModel($variants[0]);
        $productIds[] = $mainVariant->getId();

        $detail = new Detail();
        $detail->setActive($mainVariant->getActive());
        $this->getManager()->persist($detail);
        $detail->setArticle($mainVariant);
        $mainVariant->getDetails()->add($detail);

        foreach ($variants as $variant) {
            $variantConfigurator->configureVariantAttributes($variant, $detail);
        }

        $configuratorSet = $mainVariant->getConfiguratorSet();

        $this->assertEquals('Set-' . $mainVariant->getName(), $configuratorSet->getName());
        $this->assertEquals(false, $configuratorSet->getPublic());
        $this->assertEquals($variants[0]->configuratorSetType, $configuratorSet->getType());

        $groups = $configuratorSet->getGroups();

        $this->assertEquals(1, $groups->count());
        $this->assertEquals('Farbe', $groups[0]->getName());

        $options = $configuratorSet->getOptions();

        $this->assertEquals(4, $options->count());

        foreach ($options as $option) {
            $this->assertContains($option->getName(), $colorOptions);
        }
    }
}
