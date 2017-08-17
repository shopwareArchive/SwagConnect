<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Tests\Integration\Subscribers;

use ShopwarePlugins\Connect\Tests\CommandTestCaseTrait;

class CronJob extends \PHPUnit_Framework_TestCase
{
    use CommandTestCaseTrait;

    public function test_it_should_execute_import_image_cron_job()
    {
        $result = $this->runCommand('sw:cron:run ShopwareConnectImportImages');

        $this->assertEquals('Processing SwagConnect Import images', $result[0]);
    }

    public function test_it_should_execute_update_products_cron_job()
    {
        $result = $this->runCommand('sw:cron:run ShopwareConnectUpdateProducts');

        $this->assertEquals('Processing SwagConnect Update Products', $result[0]);
    }

    public function test_it_should_execute_export_dynamic_streams_cron_job()
    {
        $result = $this->runCommand('sw:cron:run Shopware_CronJob_ConnectExportDynamicStreams');

        $this->assertEquals('Processing SwagConnect Export Dynamic Streams', $result[0]);
    }
}
