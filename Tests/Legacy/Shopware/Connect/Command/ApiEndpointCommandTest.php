<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\ShopwarePlugins\Connect;

use ShopwarePlugins\Connect\Tests\CommandTestCaseTrait;

class ApiEndpointCommandTest extends ConnectTestHelper
{
    use CommandTestCaseTrait;

    public function testShouldSetNewHost()
    {
        $result = $this->runCommand('connect:endpoint:set sn.connect.local');
        $this->assertContains('Endpoint was updated', $result[1]);
        $this->assertContains('sn.connect.local', $result[1]);
    }

    public function testShouldParseUrlWithoutProtocol()
    {
        $result = $this->runCommand('connect:endpoint:set http://sn.connect.local');
        $this->assertContains('sn.connect.local', $result[1]);
        $this->assertNotContains('http://', $result[1]);
    }
}