<?php

namespace Tests\ShopwarePlugins\Connect;

use ShopwarePlugins\Connect\Components\Config;
use ShopwarePlugins\Connect\Components\Marketplace\MarketplaceSettings;

class ConfigTest extends ConnectTestHelper
{
    public function tearDown()
    {
        Shopware()->Db()->exec("
          DELETE FROM s_plugin_connect_config WHERE groupName = 'marketplace'
        ");
    }

    public function testGetConfig()
    {
        $configValue = $this->getConfigComponent()->getConfig('connectAttribute', null, 'general');
        $this->assertNotEmpty($configValue);
    }

    public function testGetConfigs()
    {
        $generalConfigs = $this->getConfigComponent()->getConfigs();

        $this->assertNotEmpty($generalConfigs);
    }

    public function testSetConfig()
    {
        $configName = 'testConfig' . rand(1, 9999);
        $this->getConfigComponent()->setConfig($configName, 1, null, 'general');

        $sql = 'SELECT * FROM s_plugin_connect_config WHERE name = ?';
        $result = Shopware()->Db()->fetchRow($sql, array($configName));

        $this->assertEquals(1, $result['value']);
        $this->assertEquals('general', $result['groupName']);
        $this->assertEquals(null, $result['shopId']);
    }

    public function testGetGeneralConfig()
    {
        $configs = $this->getConfigComponent()->getGeneralConfig();

        $sql = 'SELECT name, value FROM s_plugin_connect_config WHERE shopId IS NULL AND groupName = ?';
        $result = Shopware()->Db()->fetchPairs($sql, array('general'));

        foreach ($result as $name => $value) {
            $this->assertEquals($value, $configs[0][$name]);
        }
    }

    public function testSetGeneralConfigsArrays()
    {
        $configName = 'testConfig' . rand(1, 9999);
        $configValue = 0;
        $data = array($configName => $configValue);

        $this->getConfigComponent()->setGeneralConfigs($data);
        $sql = 'SELECT name, value FROM s_plugin_connect_config WHERE shopId IS NULL AND groupName = ? AND name = ?';
        $result = Shopware()->Db()->fetchPairs($sql, array('general', $configName));

        $this->assertNotEmpty($result);
        $this->assertEquals($configValue, $result[$configName]);
    }

    public function testGetImportConfig()
    {
        $importConfig = $this->getConfigComponent()->getImportConfig();

        $sql = 'SELECT name, value FROM s_plugin_connect_config WHERE shopId IS NULL AND groupName = ?';
        $result = Shopware()->Db()->fetchPairs($sql, array('import'));

        foreach ($result as $name => $value) {
            $this->assertEquals($value, $importConfig[$name]);
        }

    }

    public function testSetImportConfigs()
    {
        $configName = 'testConfig' . rand(1, 9999);
        $configValue = 0;
        $data = array(
            array(
                $configName => $configValue
            )
        );

        $this->getConfigComponent()->setImportConfigs($data);
        $sql = 'SELECT name, value FROM s_plugin_connect_config WHERE shopId IS NULL AND groupName = ? AND name = ?';
        $result = Shopware()->Db()->fetchPairs($sql, array('import', $configName));

        $this->assertNotEmpty($result);
        $this->assertEquals($configValue, $result[$configName]);
    }

    public function testGetExportConfig()
    {
        $this->getConfigComponent()->setConfig('testConfigArray', array(0, 1, 'value'));
        $exportConfig = $this->getConfigComponent()->getExportConfig();

        $sql = 'SELECT name, value FROM s_plugin_connect_config WHERE shopId IS NULL AND groupName = ?';
        $result = Shopware()->Db()->fetchPairs($sql, array('export'));

        foreach ($result as $name => $value) {
            if (json_decode($value, true) !== null) {
                $value = json_decode($value, true);
            }
            $this->assertEquals($value, $exportConfig[$name]);
        }
    }

    public function testSetExportConfigs()
    {
        $configName = 'testConfig' . rand(1, 9999);
        $configValue = 0;
        $data = array(
            array(
                $configName => $configValue
            )
        );

        $this->getConfigComponent()->setExportConfigs($data);
        $sql = 'SELECT name, value FROM s_plugin_connect_config WHERE shopId IS NULL AND groupName = ? AND name = ?';
        $result = Shopware()->Db()->fetchPairs($sql, array('export', $configName));

        $this->assertNotEmpty($result);
        $this->assertEquals($configValue, $result[$configName]);
    }

    public function testCompareExportConfiguration()
    {
        $data = array(
            'priceGroupForPriceExport' => 2,
            'priceFieldForPriceExport' => 2,
            'priceGroupForPurchasePriceExport' => 2,
            'priceFieldForPurchasePriceExport' => 2
        );
        $this->assertTrue($this->getConfigComponent()->compareExportConfiguration($data));

        $sql = 'SELECT name, value FROM s_plugin_connect_config WHERE groupName = ?';
        $exportConfig = Shopware()->Db()->fetchPairs($sql, array('export'));

        $this->assertFalse(
            $this->getConfigComponent()->compareExportConfiguration($exportConfig)
        );
    }

    public function testGetConfigByValue()
    {
        $configName = 'testConfig' . rand(1, 9999);
        $configValue = $configName;

        $sql = 'INSERT INTO s_plugin_connect_config (name, value, groupName) VALUES ("' . $configName . '", "' . $configValue . '", "units")';
        Shopware()->Db()->exec($sql);
        $config = $this->getConfigComponent()->getConfigByValue($configValue);

        $this->assertInstanceOf('Shopware\CustomModels\Connect\Config', $config);
        $this->assertEquals($configName, $config->getName());
        $this->assertEquals($configValue, $config->getValue());
        $this->assertEquals('units', $config->getGroupName());
    }

    public function testDeleteConfig()
    {
        $this->getConfigComponent()->setConfig('testConfigDelete', 1);
        $this->getConfigComponent()->deleteConfig('testConfigDelete');

        $this->assertNull($this->getConfigComponent()->getConfig('testConfigDelete'));
    }

    /**
     * @expectedException \Exception invalidConfigName
     */
    public function testDeleteInvalidConfigRecord()
    {
        $this->getConfigComponent()->deleteConfig('invalidConfigName');
    }

    public function testSetMarketplaceSettings()
    {
        $marketplaceSettings = new MarketplaceSettings(array(
            'marketplaceName' => 'semdemo',
            'marketplaceNetworkUrl' => 'http://semdemo.connect.de',
            'marketplaceIcon' => 'data:image/vnd.microsoft.icon;base64,AAABAAEAEBAAAAEAIABoBAAAFgAAACgAAAAQAAAAIAAAAAEAIAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAIX/8EiF//KMhf/zvIX/8/yF//OIhf/yJAAAAACF//AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAhf/wAIX/8LSF//P8hf/z/IX/8/yF//P8hf/z/IX/8/yF//O4hf/wMIX/8AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACF//Pghf/z/IX/8/yF//OUhf/yvIX/89iF//P8hf/z/IX/8zAAAAAAAAAAAAAAAAAAAAAAAAAAAIX/8AyF//GYhf/z/IX/8/yF//JkAAAAAAAAAAAAAAAAhf/zMIX/8/yF//P8hf/wtAAAAAAAAAAAAAAAAAAAAACF//BYhf/yAIX/8/yF//P8AAAAAIX/8AAAAAAAhf/wAIX/8KCF//P8hf/z/IX/8cAAAAAAAAAAAIX/8ACF//DYhf/yJIX/8gCF//P8hf/zrAAAAAAAAAAAAAAAAAAAAACF//Aghf/z/IX/8/yF//IAAAAAAIX/8ACF//Achf/z/AAAAACF//IAhf/z/IX/87AAAAAAAAAAAAAAAAAAAAAAhf/xfIX/8/yF//P8hf/xZAAAAAAAAAAAhf/zLIX/8wgAAAAAhf/yAIX/8/yF//OwAAAAAAAAAAAAAAAAhf/xFIX/8/yF//P8hf/z/IX/8AQAAAAAhf/yCIX/8/yF//Hchf/yKIX/8gCF//P8hf/zsAAAAAAAAAAAhf/wsIX/8/yF//P8hf/z/IX/8aAAAAAAhf/w4IX/8/yF//P8hf/wMIX/8iiF//IAhf/z/IX/87AAAAAAAAAAAIX/8OCF//P8hf/z/IX/8cQAAAAAAAAAAIX/8eCF//Ighf/yQIX/8ciF//H8hf/yAIX/8/yF//OwAAAAAAAAAACF//Dghf/z/IX/8OwAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACF//P0hf/yAIX/8gCF//P8hf/zsAAAAAAAAAAAhf/wmIX/8GQAAAAAAAAAAAAAAAAAAAAAAAAAAIX/8ACF//DQhf/z/IX/8iCF//IAhf/z/IX/87AAAAAAAAAAAAAAAACF//AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAhf/xyIX/8egAAAAAhf/yAIX/8/yF//PIAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAhf/wAAAAAAAAAAAAAAAAAIX/8fyF//P4hf/w8AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACF//Fwhf/wRIX/8AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA/wcAAP4DAAD8AQAA/HEAAPj5AADw+AAA6PkAAMjxAACQ4wAAkOcAAJjvAADg/wAA4P8AAPj/AAD9/wAA//8AAA==',
            'marketplaceLogo' => 'data:image/png;base64,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',
            'isDefault' => false,
        ));

        $this->getConfigComponent()->setMarketplaceSettings($marketplaceSettings);

        $sql = 'SELECT * FROM s_plugin_connect_config WHERE groupName = ?';
        $result = Shopware()->Db()->fetchAll($sql, array('marketplace'));

        $this->assertEquals(5, count($result));

        $this->assertEquals('semdemo', $result[0]['value']);
    }

    public function testGetUnitsMappings()
    {
        $this->getConfigComponent()->setConfig('testConfigUnit1', 'localUnit1');
        $this->getConfigComponent()->setConfig('testConfigUnit2', '');
        $unitsMapping = $this->getConfigComponent()->getUnitsMappings();

        $sql = 'SELECT name, value FROM s_plugin_connect_config WHERE shopId IS NULL AND groupName = ?';
        $result = Shopware()->Db()->fetchPairs($sql, array('units'));

        foreach ($result as $name => $value) {
            $this->assertEquals($value, $unitsMapping[$name]);
        }
    }

    public static function tearDownAfterClass()
    {
        $sql = 'DELETE FROM s_plugin_connect_config WHERE name LIKE  "testConfig%"';
        Shopware()->Db()->exec($sql);
    }

    private function getConfigComponent()
    {
        return new Config(Shopware()->Models());
    }
}