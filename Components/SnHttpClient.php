<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Components;

use Firebase\JWT\JWT;
use Shopware\Components\HttpClient\HttpClientInterface;
use Shopware\Connect\Gateway;

class SnHttpClient
{
    /**
     * @var \Shopware\Components\HttpClient\HttpClientInterface
     */
    private $httpClient;

    /**
     * @var \Shopware\Connect\Gateway
     */
    private $gateway;

    /**
     * @var \ShopwarePlugins\Connect\Components\Config
     */
    private $configComponent;

    public function __construct(
        HttpClientInterface $httpClient,
        Gateway $gateway,
        Config $config
    ) {
        $this->httpClient = $httpClient;
        $this->gateway = $gateway;
        $this->configComponent = $config;
    }

    /**
     * Call SocialNetwork REST API
     *
     * @param string $path
     * @param array $data
     * @return \Shopware\Components\HttpClient\Response
     */
    public function sendRequestToConnect($path, array $data = [])
    {
        $host = $this->configComponent->getConfig('connectDebugHost');
        if (!$host || $host == '') {
            $host = $this->configComponent->getMarketplaceUrl();
        }

        $shopId = $this->gateway->getShopId();
        $key = $this->configComponent->getConfig('apiKey');
        $token = [
            'iss' => $shopId,
            'aud' => 'SocialNetwork',
            'iat' => time(),
            'nbf' => time(),
            'exp' => time() + (60),
            'content' => $data
        ];
        $connectAuthKey = JWT::encode($token, $key);
        $url = $host . '/rest/' . $path;

        $response = $this->httpClient->post(
            $url,
            [
                'content-type' => 'application/json',
                'X-Shopware-Connect-Shop' => $shopId,
                'X-Shopware-Connect-Key' => $connectAuthKey
            ]
        );

        return $response;
    }
}
