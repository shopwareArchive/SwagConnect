<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Services;

use SebastianBergmann\GlobalState\RuntimeException;
use Shopware\Connect\SDK;
use ShopwarePlugins\Connect\Components\ConfigFactory;
use ShopwarePlugins\Connect\Components\SnHttpClient;

/**
 * Class RemoteShopService
 * @package ShopwarePlugins\Connect\Services
 */
class RemoteShopService
{
    /**
     * @var SDK
     */
    private $sdk;

    /**
     * RemoteShopService constructor.
     * @param SDK $sdk
     */
    public function __construct(SDK $sdk)
    {
        $this->sdk = $sdk;
    }

    /**
     * @param $shopId
     * @return bool
     */
    public function isPingRemoteShopSuccessful($shopId)
    {
        try {
            $this->tryPingRemoteShop($shopId);
        } catch (\RuntimeException $exception) {
            return $this->handlePingRemoteShopError($shopId, $exception);
        }

        return true;
    }

    /**
     * @param $shopId
     * @throws RuntimeException
     */
    private function tryPingRemoteShop($shopId)
    {
        if ($this->sdk->pingShop($shopId) !== 'pong') {
            throw new RuntimeException("Ping was not returning excepted result 'pong'.");
        }
    }

    /**
     * @param $shopId
     * @param $exception
     * @return bool
     */
    private function handlePingRemoteShopError($shopId, $exception)
    {
        if ($this->isExceptionFatal($exception)) {
            $this->sendAddToBaskedFailedNotificationToSocialNetwork($shopId);

            return false;
        }

        return true;
    }

    /**
     * Checks if the exception is fatal by looking at the exceptions
     * message. The exception is not fatal when the ping service is
     * just not available yet. Receiving this exception from SDK would
     * actually be a successful ping of the remote shop. This is needed
     * because of backwards compatibility with older SDK versions less
     * than v2.0.8
     *
     * @param $exception
     * @return bool
     */
    public function isExceptionFatal($exception)
    {
        if (strpos($exception->getMessage(),
            "Uncaught Shopware\Connect\SecurityException: No Authorization to call service 'ping'.") !== false) {
            return false;
        }

        return true;
    }

    /**
     * @return SnHttpClient
     * @throws \Exception
     * @todo: refactor when using 5.2 plugin base.
     */
    private function getSnHttpClient()
    {
        return new SnHttpClient(
            Shopware()->Container()->get('http_client'),
            new \Shopware\Connect\Gateway\PDO(Shopware()->Db()->getConnection()),
            ConfigFactory::getConfigInstance()
        );
    }

    /**
     * @param $shopId
     */
    public function sendAddToBaskedFailedNotificationToSocialNetwork($shopId)
    {
        try {
            $this->getSnHttpClient()->sendRequestToConnect(
                'account/add-to-basket-failed',
                [
                    'supplierShopId' => $shopId
                ]
            );
        } catch (\Exception $exception) {
            Shopware()->PluginLogger()->error('Unable to send request to SocialNetwork: ' . $exception);
        }
    }
}
