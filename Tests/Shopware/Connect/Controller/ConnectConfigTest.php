<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\ShopwarePlugins\Connect;

use ShopwarePlugins\Connect\Components\ProductQuery\BaseProductQuery;

class ConnectConfigTest extends \Enlight_Components_Test_Controller_TestCase
{
    public function setUp()
    {
        parent::setUp();

        // disable auth and acl
        Shopware()->Plugins()->Backend()->Auth()->setNoAuth();
        Shopware()->Plugins()->Backend()->Auth()->setNoAcl();
    }

    public function tearDown()
    {
        $longDescription = BaseProductQuery::LONG_DESCRIPTION_FIELD;
        Shopware()->Db()->exec('DELETE FROM s_plugin_connect_config');
        Shopware()->Db()->executeQuery(
            "INSERT INTO `s_plugin_connect_config`
            (`name`, `value`, `groupName`) VALUES
            ('priceGroupForPriceExport', 'EK', 'export'),
            ('priceGroupForPurchasePriceExport', 'EK', 'export'),
            ('priceFieldForPriceExport', 'price', 'export'),
            ('priceFieldForPurchasePriceExport', 'basePrice', 'export'),
            ('exportPriceMode', '[\"price\", \"purchasePrice\"]', 'export'),
            ('detailProductNoIndex', '1', 'general'),
            ('detailShopInfo', '1', 'general'),
            ('checkoutShopInfo', '1', 'general'),
            ('$longDescription', '1', 'export'),
            ('importImagesOnFirstImport', '0', 'import'),
            ('autoUpdateProducts', '1', 'export'),
            ('overwriteProductName', '1', 'import'),
            ('overwriteProductPrice', '1', 'import'),
            ('overwriteProductImage', '1', 'import'),
            ('overwriteProductShortDescription', '1', 'import'),
            ('overwriteProductLongDescription', '1', 'import'),
            ('overwriteProductAdditionalDescription', '1', 'import'),
            ('logRequest', '1', 'general'),
            ('showShippingCostsSeparately', '0', 'general'),
            ('articleImagesLimitImport', '10', 'import');
            "
        );
    }

    public function testGetGeneralAction()
    {
        $this->dispatch('backend/ConnectConfig/getGeneral');
        $this->assertEquals(200, $this->Response()->getHttpResponseCode());
        $this->assertTrue($this->View()->success);
        $returnData = $this->View()->data;

        $this->assertNotEmpty($returnData);
    }

    public function testSaveGeneralAction()
    {
        $expectations = [
            'id' => null,
            'activateProductsAutomatically' => '0',
            'createCategoriesAutomatically' => '1',
            'createUnitsAutomatically' => '1',
            'exportDomain' => '',
            'checkoutShopInfo' => '1',
            'detailProductNoIndex' => '1',
            'detailShopInfo' => '1',
            'logRequest' => '1',
            'connectDebugHost' => 'stage.connect.de',
            'apiKey' => '58dfcc22-0ab7-4bf6-8eff-e0d2c9455019',
            'showShippingCostsSeparately' => '0',
            'articleImagesLimitImport' => '10',
        ];


        $this->Request()
            ->setMethod('POST')
            ->setPost('data', $expectations);
        $this->dispatch('backend/ConnectConfig/saveGeneral');

        unset($expectations['id']);

        $sql= "SELECT * from s_plugin_connect_config WHERE groupName = 'general'";
        $result = Shopware()->Db()->fetchAll($sql);

        foreach ($result as $config) {
            $this->assertArrayHasKey($config['name'], $expectations);
            $this->assertEquals($expectations[$config['name']], $config['value']);
        }

        $sql= "SELECT * from s_plugin_connect_config WHERE `name` = 'id' AND groupName = 'general'";
        $result = Shopware()->Db()->fetchAll($sql);
        $this->assertEmpty($result);
    }

    public function testGetConnectUnitsAction()
    {
        $this->dispatch('backend/ConnectConfig/getConnectUnits');
        $this->assertEquals(200, $this->Response()->getHttpResponseCode());
        $this->assertTrue($this->View()->success);
        $this->assertTrue(isset($this->View()->data));
    }

    public function testAdoptUnitsAction()
    {
        Shopware()->Db()->exec("DELETE FROM s_core_units WHERE unit = 'week'");

        Shopware()->Db()->executeQuery(
            "INSERT INTO `s_plugin_connect_config`
            (`name`, `value`, `groupName`) VALUES
            ('week', '', 'units');
            "
        );

        $this->dispatch('backend/ConnectConfig/adoptUnits');
        $this->assertEquals(200, $this->Response()->getHttpResponseCode());
        $this->assertTrue($this->View()->success);
    }

    public function testSaveUnitsMappingAction()
    {
        Shopware()->Db()->exec("DELETE FROM s_plugin_connect_config WHERE name = 'ml' AND groupName = 'units'");

        $this->Request()
            ->setMethod('POST')
            ->setPost('data', [
                'connectUnit' => 'ml',
                'shopwareUnitKey' => 'l',
            ]);
        $this->dispatch('backend/ConnectConfig/saveUnitsMapping');

        $this->assertEquals(200, $this->Response()->getHttpResponseCode());
        $this->assertTrue($this->View()->success);
    }

    /**
     * @test
     */
    public function it_returns_error_when_price_and_purchase_price_are_same_field()
    {
        $this->Request()
            ->setMethod('POST')
            ->setPost('data', [
                'exportPriceMode' => ['price', 'purchasePrice'],
                'priceGroupForPurchasePriceExport' => 'EK',
                'priceFieldForPurchasePriceExport' => 'price',
                'priceGroupForPriceExport' => 'EK',
                'priceFieldForPriceExport' => 'price',
            ]);
        $this->dispatch('backend/ConnectConfig/saveExport');

        $this->assertEquals(200, $this->Response()->getHttpResponseCode());
        $this->assertFalse($this->View()->success);
        $this->assertEquals('Endkunden-VK und Listenverkaufspreis müssen an verschiedene Felder angeschlossen sein', $this->View()->message);
    }

    /**
     * @test
     */
    public function it_returns_error_when_customer_group_for_price_is_invalid()
    {
        $this->Request()
            ->setMethod('POST')
            ->setPost('data', [
                'exportPriceMode' => ['price'],
                'priceGroupForPriceExport' => 'XX',
                'priceFieldForPriceExport' => 'basePrice',
            ]);
        $this->dispatch('backend/ConnectConfig/saveExport');

        $this->assertEquals(200, $this->Response()->getHttpResponseCode());
        $this->assertFalse($this->View()->success);
        $this->assertEquals('Ungültige Kundengruppe', $this->View()->message);
    }

    /**
     * @test
     */
    public function it_returns_error_when_at_least_one_article_has_not_supported_price()
    {
        $this->Request()
            ->setMethod('POST')
            ->setPost('data', [
                'priceGroupForPriceExport' => 'EK',
                'priceGroupForPurchasePriceExport' => 'EK',
                'priceFieldForPurchasePriceExport' => 'price',
                'priceFieldForPriceExport' => 'basePrice',
            ]);
        $this->dispatch('backend/ConnectConfig/saveExport');

        $this->assertEquals(200, $this->Response()->getHttpResponseCode());
        $this->assertFalse($this->View()->success);
        $this->assertEquals('Preisfeld ist nicht gepflegt. Bei einigen Produkten ist der Preis 0', $this->View()->message);
    }

    /**
     * todo: fix me
     */
    public function it_returns_error_when_customer_group_for_purchase_price_is_invalid()
    {
        $this->Request()
            ->setMethod('POST')
            ->setPost('data', [
                'exportPriceMode' => ['price', 'purchasePrice'],
                'priceGroupForPriceExport' => 'EK',
                'priceGroupForPurchasePriceExport' => 'XX',
                'priceFieldForPurchasePriceExport' => 'basePrice',
                'priceFieldForPriceExport' => 'price',
            ]);
        $this->dispatch('backend/ConnectConfig/saveExport');

        $this->assertEquals(200, $this->Response()->getHttpResponseCode());
        $this->assertFalse($this->View()->success);
        $this->assertEquals('Ungültige Kundengruppe', $this->View()->message);
    }

    /**
     * todo: fix me
     */
    public function it_returns_error_when_at_least_one_article_has_not_supported_purchase_price()
    {
        $this->Request()
            ->setMethod('POST')
            ->setPost('data', [
                'exportPriceMode' => ['price', 'purchasePrice'],
                'priceGroupForPriceExport' => 'EK',
                'priceGroupForPurchasePriceExport' => 'EK',
                'priceFieldForPurchasePriceExport' => 'pseudoPrice',
                'priceFieldForPriceExport' => 'price',
            ]);
        $this->dispatch('backend/ConnectConfig/saveExport');

        $this->assertEquals(200, $this->Response()->getHttpResponseCode());
        $this->assertFalse($this->View()->success);
        $this->assertEquals('Preisfeld ist nicht gepflegt. Bei einigen Produkten ist der Preis 0', $this->View()->message);
    }
}
