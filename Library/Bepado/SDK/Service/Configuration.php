<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * @version $Revision$
 */

namespace Bepado\SDK\Service;

use Bepado\SDK\Gateway;
use Bepado\SDK\HttpClient;
use Bepado\SDK\Struct;

/**
 * Service to store configuration updates
 *
 * @version $Revision$
 */
class Configuration
{
    /**
     * Gateway to shop configuration
     *
     * @var Gateway\ShopConfiguration
     */
    protected $configuration;

    /**
     * HTTP Client
     *
     * @var HttpClient
     */
    protected $httpClient;

    /**
     * Struct verificator
     *
     * @var Struct\VerificatorDispatcher
     */
    protected $verificator;

    /**
     * API Key
     *
     * @var string
     */
    protected $apiKey;

    /**
     * Construct from gateway
     *
     * @param Gateway\ShopConfiguration $gateway
     * @param Struct\VerificatorDispatcher $verificator
     * @return void
     */
    public function __construct(
        Gateway\ShopConfiguration $configuration,
        HttpClient $httpClient,
        $apiKey,
        Struct\VerificatorDispatcher $verificator
    ) {
        $this->configuration = $configuration;
        $this->httpClient = $httpClient;
        $this->apiKey = $apiKey;
        $this->verificator = $verificator;
    }

    /**
     * Store shop configuration updates
     *
     * @return void
     */
    public function update()
    {
        $response = $this->httpClient->request(
            'POST',
            '/sdk/configuration',
            json_encode(
                array(
                    'apiKey' => $this->apiKey
                )
            ),
            array(
                'Content-Type: application/json',
            )
        );

        if ($response->status >= 400) {
            $message = null;
            if (($error = json_decode($response->body)) &&
                isset($error->message)) {
                $message = $error->message;
            }
            throw new \RuntimeException("Loading configuration failed: " . $message);
        }

        $configurations = json_decode($response->body, true);
        foreach ($configurations as $configuration) {
            $this->configuration->setShopConfiguration(
                $configuration['shopId'],
                new Struct\ShopConfiguration(
                    array(
                        'serviceEndpoint' => $configuration['serviceEndpoint'],
                        'shippingCost' => $configuration['shippingCost'],
                        'displayName' => $configuration['shopDisplayName'],
                        'url' => $configuration['shopUrl'],
                    )
                )
            );
        }
    }
}
