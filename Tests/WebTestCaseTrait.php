<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Tests;

use Doctrine\DBAL\Connection;

trait WebTestCaseTrait
{
    use KernelTestCaseTrait;

    /**
     * @before
     */
    public function beforeStartSetToReboot()
    {
        $this->setAutoReboot();
        $this->disableCommonFixtures();
    }

    /**
     * @return TestClient
     */
    public static function createClient()
    {
        self::getKernel()
            ->getContainer()
            ->get('dbal_connection')
            ->exec('UPDATE s_core_auth SET apiKey=123 WHERE id=1');

        return new TestClient(self::getKernel(), ['HTTP_HOST' => parse_url('http://127.0.0.1', PHP_URL_HOST)]);
    }

    /**
     * @return Connection
     */
    public static function getDbalConnection()
    {
        return self::getKernel()->getContainer()->get('dbal_connection');
    }

    /**
     * @return TestClient
     */
    public static function createBackendClient()
    {
        $client = self::createClient();

        /** @var \Shopware_Plugins_Backend_Auth_Bootstrap $auth */
        $auth = self::getKernel()->getContainer()->get('plugins')->Backend()->Auth();
        $auth->setNoAuth();
        $auth->setNoAcl();

        self::getKernel()->getContainer()->get('front')->setResponse('Enlight_Controller_Response_ResponseTestCase');

        return $client;
    }
}
