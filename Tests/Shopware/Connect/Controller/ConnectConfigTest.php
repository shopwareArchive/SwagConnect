<?php

namespace Tests\ShopwarePlugins\Connect;

class ConnectConfigTest extends \Enlight_Components_Test_Controller_TestCase
{
    public  function setUp()
    {
        parent::setUp();

        // disable auth and acl
        Shopware()->Plugins()->Backend()->Auth()->setNoAuth();
        Shopware()->Plugins()->Backend()->Auth()->setNoAcl();
    }

    public function tearDown()
    {
        Shopware()->Db()->exec('DELETE FROM s_plugin_connect_config');
        Shopware()->Db()->executeQuery(
            "INSERT INTO `s_plugin_connect_config`
            (`name`, `value`, `groupName`) VALUES
            ('priceGroupForPriceExport', 'EK', 'export'),
            ('priceGroupForPurchasePriceExport', 'EK', 'export'),
            ('priceFieldForPriceExport', 'price', 'export'),
            ('priceFieldForPurchasePriceExport', 'basePrice', 'export'),
            ('detailProductNoIndex', '1', 'general'),
            ('detailShopInfo', '1', 'general'),
            ('checkoutShopInfo', '1', 'general'),
            ('alternateDescriptionField', 'a.descriptionLong', 'export'),
            ('connectAttribute', '19', 'general'),
            ('importImagesOnFirstImport', '0', 'import'),
            ('autoUpdateProducts', '1', 'export'),
            ('overwriteProductName', '1', 'import'),
            ('overwriteProductPrice', '1', 'import'),
            ('overwriteProductImage', '1', 'import'),
            ('overwriteProductShortDescription', '1', 'import'),
            ('overwriteProductLongDescription', '1', 'import'),
            ('logRequest', '1', 'general'),
            ('shippingCostsPage', '6', 'general'),
            ('showShippingCostsSeparately', '0', 'general'),
            ('articleImagesLimitImport', '10', 'general');
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

        $expectations = array(
            'activateProductsAutomatically' => '0',
            'createCategoriesAutomatically' => '1',
            'createUnitsAutomatically' => '1',
            'exportDomain' => '',
            'shippingCostsPageName' => 'Versand und Zahlung',
            'shippingCostsPage' => '6',
            'checkoutShopInfo' => '1',
            'detailProductNoIndex' => '1',
            'detailShopInfo' => '1',
            'logRequest' => '1',
            'connectDebugHost' => 'stage.connect.de',
            'connectAttribute' => '18',
            'apiKey' => '58dfcc22-0ab7-4bf6-8eff-e0d2c9455019',
            'isDefaultShop' => '1',
            'shopId' => '15',
            'hasSsl' => '0',
            'showShippingCostsSeparately' => '0',
            'articleImagesLimitImport' => '10',
        );


        $this->Request()
            ->setMethod('POST')
            ->setPost('data', $expectations);
        $this->dispatch('backend/ConnectConfig/saveGeneral');

        $sql= "SELECT * from s_plugin_connect_config WHERE groupName = 'general'";
        $result = Shopware()->Db()->fetchAll($sql);

        foreach ($result as $config) {
            $this->assertArrayHasKey($config['name'], $expectations);
            $this->assertEquals($expectations[$config['name']], $config['value']);
        }

        $sql= "SELECT * from s_plugin_connect_config WHERE (`name` = 'shopId' OR `name` = 'isDefaultShop') AND groupName = 'general'";
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
}
 