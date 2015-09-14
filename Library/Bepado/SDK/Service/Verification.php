<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Bepado\SDK\Service;

use Bepado\SDK\Gateway;
use Bepado\SDK\HttpClient;
use Bepado\SDK\SDK;

/**
 * Verification service
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */
class Verification
{
    /**
     * HTTP Client
     *
     * @var HttpClient
     */
    protected $httpClient;

    /**
     * Shop configuration gateway
     *
     * @var Gateway\ShopConfiguration
     */
    protected $config;

    public function __construct(
        HttpClient $httpClient,
        Gateway\ShopConfiguration $config
    ) {
        $this->httpClient = $httpClient;
        $this->config = $config;
    }

    /**
     * Checks if verification is still valid
     *
     * @return bool
     * @deprecated Use {@link isVerified()} instead.
     */
    public function isValid()
    {
        // Legacy fallback, does not force re-verification anymore
        return $this->isVerified();
    }

    /**
     * Check if a successful SDK verification has happend before.
     *
     * @return bool
     */
    public function isVerified()
    {
        return $this->config->getShopId() !== null;
    }

    /**
     * Verify the shops API key and stores the shopId in the response for
     * future use
     *
     * @param string $apiKey
     * @param string $apiEndpointUrl
     * @return void
     */
    public function verify($apiKey, $apiEndpointUrl)
    {
        $response = $this->httpClient->request(
            'POST',
            '/sdk/verify',
            json_encode(
                array(
                    'apiKey' => $apiKey,
                    'apiEndpointUrl' => $apiEndpointUrl,
                    'version' => SDK::VERSION,
                )
            ),
            array(
                'Content-Type: application/json',
            )
        );

        if ($response->status >= 400) {
            if (($error = json_decode($response->body)) && isset($error->message)) {
                throw new \RuntimeException("Verification failed: " . $error->message);
            }

            throw new \RuntimeException("Verification failed with HTTP status " . $response->status);
        }

        if ($response->body &&
            $return = json_decode($response->body)) {
            $this->config->setShopId($return->shopId);
            $this->config->setConfig(SDK::CONFIG_PRICE_TYPE, $return->priceType);
        } else {
            throw new \RuntimeException("Response could not be processed: " . $response->body);
        }
        return;
    }
}
