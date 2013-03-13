<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * @version 1.0.0snapshot201303061109
 */

namespace Bepado\SDK\Service;

use Bepado\SDK\HttpClient;
use Bepado\SDK\Gateway;

/**
 * Verification service
 *
 * @version 1.0.0snapshot201303061109
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
     * Shop cinfiguration gateway
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
     * @return void
     */
    public function isValid()
    {
        return $this->config->getShopId() &&
            ($this->config->getLastVerificationDate() > (time() - (7 * 86400)));
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
                )
            ),
            array(
                'Content-Type: application/json',
            )
        );

        if ($response->status >= 400) {
            $message = null;
            if ($error = json_decode($response->body) &&
                isset($error->message)) {
                $message = $error->message;
            }
            throw new \RuntimeException("Verification failed: " . $message);
        }

        if ($response->body &&
            $return = json_decode($response->body)) {
            $this->config->setShopId($return->shopId);
            $this->config->setCategories((array) $return->categories);
        } else {
            throw new \RuntimeException("Response could not be processed: " . $response->body);
        }
        return;
    }
}
