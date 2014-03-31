<?php

namespace Tests\Shopware\Bepado;

use Shopware\Bepado\Components\Config;

class ConfigTest extends BepadoTestHelper
{
    public function testGetConfig()
    {
        $configValue = $this->getConfigComponent()->getConfig('bepadoAttribute', null, 'general');
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

        $sql = 'SELECT * FROM s_plugin_bepado_config WHERE name = ?';
        $result = Shopware()->Db()->fetchRow($sql, array($configName));

        $this->assertEquals(1, $result['value']);
        $this->assertEquals('general', $result['groupName']);
        $this->assertEquals(null, $result['shopId']);
    }

    public function testGetGeneralConfigArrays()
    {
        $configs = $this->getConfigComponent()->getGeneralConfigArrays();

        $sql = 'SELECT name, value FROM s_plugin_bepado_config WHERE shopId IS NULL AND groupName = ?';
        $result = Shopware()->Db()->fetchPairs($sql, array('general'));

        foreach ($result as $name => $value) {
            $this->assertEquals($value, $configs[0][$name]);
        }
    }

    public function testSetGeneralConfigsArrays()
    {
        $configName = 'testConfig' . rand(1, 9999);
        $configValue = 0;
        $data = array(
            array(
                'shopId' => 1,
                'isDefaultShop' => true,
                $configName => $configValue,
            )
        );

        $this->getConfigComponent()->setGeneralConfigsArrays($data);
        $sql = 'SELECT name, value FROM s_plugin_bepado_config WHERE shopId IS NULL AND groupName = ? AND name = ?';
        $result = Shopware()->Db()->fetchPairs($sql, array('general', $configName));

        $this->assertNotEmpty($result);
        $this->assertEquals($configValue, $result[$configName]);
    }

    public function testGetImportConfig()
    {
        $importConfig = $this->getConfigComponent()->getImportConfig();

        $sql = 'SELECT name, value FROM s_plugin_bepado_config WHERE shopId IS NULL AND groupName = ?';
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
        $sql = 'SELECT name, value FROM s_plugin_bepado_config WHERE shopId IS NULL AND groupName = ? AND name = ?';
        $result = Shopware()->Db()->fetchPairs($sql, array('import', $configName));

        $this->assertNotEmpty($result);
        $this->assertEquals($configValue, $result[$configName]);
    }

    public function testGetExportConfig()
    {
        $exportConfig = $this->getConfigComponent()->getExportConfig();

        $sql = 'SELECT name, value FROM s_plugin_bepado_config WHERE shopId IS NULL AND groupName = ?';
        $result = Shopware()->Db()->fetchPairs($sql, array('export'));

        foreach ($result as $name => $value) {
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
        $sql = 'SELECT name, value FROM s_plugin_bepado_config WHERE shopId IS NULL AND groupName = ? AND name = ?';
        $result = Shopware()->Db()->fetchPairs($sql, array('export', $configName));

        $this->assertNotEmpty($result);
        $this->assertEquals($configValue, $result[$configName]);
    }

    public function testSetUnitsMapping()
    {
        $unitName = 'testConfigUnit' . rand(1, 9999);
        $bepadoUnit = 'kg';
        $data = array(
            array(
                'shopwareUnitKey' => $unitName,
                'bepadoUnit' => $bepadoUnit
            )
        );
        $this->getConfigComponent()->setUnitsMapping($data);

        $sql = 'SELECT name, value FROM s_plugin_bepado_config WHERE name = ? AND groupName = ?';
        $result = Shopware()->Db()->fetchRow($sql, array($unitName, 'units'));

        $this->assertEquals($unitName, $result['name']);
        $this->assertEquals($bepadoUnit, $result['value']);
    }

    public function testCompareExportConfiguration()
    {
        $data = array(
            array(
                'priceGroupForPriceExport' => 2,
                'priceFieldForPriceExport' => 2,
                'priceGroupForPurchasePriceExport' => 2,
                'priceFieldForPurchasePriceExport' => 2
            )
        );
        $this->assertTrue($this->getConfigComponent()->compareExportConfiguration($data));

        $sql = 'SELECT name, value FROM s_plugin_bepado_config WHERE groupName = ?';
        $exportConfig = Shopware()->Db()->fetchPairs($sql, array('export'));

        $this->assertFalse(
            $this->getConfigComponent()->compareExportConfiguration(array($exportConfig))
        );
    }

    public function testGetConfigByValue()
    {
        $configName = 'testConfig' . rand(1, 9999);
        $configValue = $configName;

        $sql = 'INSERT INTO s_plugin_bepado_config (name, value, groupName) VALUES ("'.$configName.'", "'.$configValue.'", "units")';
        Shopware()->Db()->exec($sql);
        $config = $this->getConfigComponent()->getConfigByValue($configValue);

        $this->assertInstanceOf('Shopware\CustomModels\Bepado\Config', $config);
        $this->assertEquals($configName, $config->getName());
        $this->assertEquals($configValue, $config->getValue());
        $this->assertEquals('units', $config->getGroupName());
    }

    public static function tearDownAfterClass()
    {
        $sql = 'DELETE FROM s_plugin_bepado_config WHERE name LIKE  "testConfig%"';
        Shopware()->Db()->exec($sql);
    }

    private function getConfigComponent()
    {
        return new Config(Shopware()->Models());
    }
}