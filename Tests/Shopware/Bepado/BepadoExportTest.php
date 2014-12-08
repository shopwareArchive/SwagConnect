<?php

namespace Tests\Shopware\Bepado;


use Shopware\Bepado\Components\BepadoExport;

class BepadoExportTest extends BepadoTestHelper
{
    /**
     * @var \Shopware\Bepado\Components\BepadoExport
     */
    private $bepadoExport;

    public function setUp()
    {
        $this->bepadoExport = new BepadoExport($this->getHelper(), $this->getSDK(), Shopware()->Models());
    }

    public function testExport()
    {
        $sql = 'UPDATE s_plugin_bepado_items SET export_status = "insert" WHERE article_id = ?';
        Shopware()->Db()->executeQuery($sql, array(2));

        $errors = $this->bepadoExport->export(array(2));

        $this->assertEmpty($errors);

        $sql = 'SELECT export_status, export_message FROM s_plugin_bepado_items WHERE source_id = ?';
        $row = Shopware()->Db()->fetchRow($sql, array(2));

        $this->assertEquals('update', $row['export_status']);
        $this->assertNull($row['export_message']);
    }

    public function testExportErrors()
    {
        $errors = $this->bepadoExport->export(array(4));

        $this->assertNotEmpty($errors);

        $sql = 'SELECT export_status, export_message FROM s_plugin_bepado_items WHERE article_id = ?';
        $row = Shopware()->Db()->fetchRow($sql, array(4));

        $this->assertEquals('error', $row['export_status']);
        $this->assertContains('The purchasePrice is not allowed to be 0 or smaller.', $row['export_message']);
    }
}
 