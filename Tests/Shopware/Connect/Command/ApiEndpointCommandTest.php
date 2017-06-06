<?php

namespace Tests\ShopwarePlugins\Connect;


class ApiEndpointCommandTest extends ConnectTestHelper
{
    use CommandTestCaseTrait;

    public function testShouldSetNewHost()
    {
        $result = $this->runCommand('connect:endpoint:set sn.connect.local');
        $this->assertContains('Endpoint was updated', $result[1]);
    }

    public function testShouldParseUrlWithoutProtocol()
    {
        $result = $this->runCommand('connect:endpoint:set http://sn.connect.local');
        $this->assertContains('sn.connect.local', $result[1]);
        $this->assertNotContains('http://', $result[1]);
    }
}