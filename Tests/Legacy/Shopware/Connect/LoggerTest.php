<?php

namespace Tests\ShopwarePlugins\Connect;

use ShopwarePlugins\Connect\Components\Logger;
use Symfony\Component\Config\Definition\Exception\Exception;

class LoggerTest extends ConnectTestHelper
{
    protected $logger;

    protected function getLogger()
    {
        if (!$this->logger) {
            $this->logger = new Logger(Shopware()->Db());
        }
        return $this->logger;
    }

    public function testWriteWithException()
    {
        $logger = $this->getLogger();

        $message = 'Example Exception '.rand(1, 9999);
        $logger->write(true, null, new \Exception($message));

        $sql = 'SELECT id FROM s_plugin_connect_log WHERE response LIKE ?';
        $id = Shopware()->Db()->fetchOne($sql, array('%' . $message . '%'));

        $this->assertNotEmpty($id);
    }

    public function testWriteWithString()
    {
        $logger = $this->getLogger();

        $message = 'Example Message '.rand(1, 9999);
        $logger->write(false, null, $message);

        $sql = 'SELECT id FROM s_plugin_connect_log WHERE response = ?';
        $id = Shopware()->Db()->fetchOne($sql, array($message));

        $this->assertNotEmpty($id);
    }
}