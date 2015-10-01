<?php

namespace Tests\Shopware\Bepado;

class BepadoConfigTest extends \Enlight_Components_Test_Controller_TestCase
{
    public  function setUp()
    {
        parent::setUp();

        // disable auth and acl
        Shopware()->Plugins()->Backend()->Auth()->setNoAuth();
        Shopware()->Plugins()->Backend()->Auth()->setNoAcl();
    }

    public function testGetGeneralAction()
    {
        $this->dispatch('backend/BepadoConfig/getGeneral');
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
            'bepadoDebugHost' => 'stage.bepado.de',
            'bepadoAttribute' => '18',
            'apiKey' => '58dfcc22-0ab7-4bf6-8eff-e0d2c9455019',
            'isDefaultShop' => '1',
            'shopId' => '15',
            'hasSsl' => '0',
        );


        $this->Request()
            ->setMethod('POST')
            ->setPost('data', $expectations);
        $this->dispatch('backend/BepadoConfig/saveGeneral');

        $sql= "SELECT * from s_plugin_bepado_config WHERE groupName = 'general'";
        $result = Shopware()->Db()->fetchAll($sql);

        foreach ($result as $config) {
            $this->assertArrayHasKey($config['name'], $expectations);
            $this->assertEquals($expectations[$config['name']], $config['value']);
        }

        $sql= "SELECT * from s_plugin_bepado_config WHERE (`name` = 'shopId' OR `name` = 'isDefaultShop') AND groupName = 'general'";
        $result = Shopware()->Db()->fetchAll($sql);
        $this->assertEmpty($result);
    }
}
 