<?php


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
    )
    {
        $this->httpClient = $httpClient;
        $this->gateway = $gateway;
        $this->configComponent = $config;
    }

    /**
     * Call SocialNetwork REST API
     *
     * @param array $data
     * @param string $path
     * @return \Shopware\Components\HttpClient\Response
     */
    public function sendRequestToConnect(array $data, $path)
    {
        $host = $this->configComponent->getConfig('connectDebugHost');
        if ($host) {
            $host = $this->configComponent->getSocialNetworkPrefix() . $host;
        } else {
            $host = $this->configComponent->getSocialNetworkPrefix() . $this->configComponent->getMarketplaceUrl();
        }

        $shopId = $this->gateway->getShopId();
        $key = $this->configComponent->getConfig('apiKey');
        $token = array(
            "iss" => $shopId,
            "aud" => "SocialNetwork",
            "iat" => time(),
            "nbf" => time(),
            "exp" => time() + (60),
            "content" => $data
        );
        $connectAuthKey = JWT::encode($token, $key);
        $url = $host . '/rest/' . $path;

        $response = $this->httpClient->post(
            $url,
            array(
                'content-type' => 'application/json',
                'X-Shopware-Connect-Shop' => $shopId,
                'X-Shopware-Connect-Key' => $connectAuthKey
            )
        );

        return $response;
    }
}