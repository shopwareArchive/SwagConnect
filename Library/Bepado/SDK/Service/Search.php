<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Bepado\SDK\Service;

use Bepado\SDK\HttpClient;
use Bepado\SDK\Struct;

/**
 * Search service
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */
class Search
{
    /**
     * HTTP Client
     *
     * @var HttpClient
     */
    protected $httpClient;

    /**
     * @var string
     */
    protected $apiKey;

    /**
     * @var int
     */
    protected $shopId;

    public function __construct(
        HttpClient $httpClient,
        $apiKey,
        $shopId
    ) {
        $this->httpClient = $httpClient;
        $this->apiKey = $apiKey;
        $this->shopId = $shopId;
    }

    /**
     * Verify the shops API key and stores the shopId in the response for
     * future use
     *
     * @param Struct\Search $search
     * @return Struct\SearchResult
     */
    public function search(Struct\Search $search)
    {
        $data = (array)$search;
        $data['shopId'] = $this->shopId;
        ksort($data);
        $data['hash'] = hash_hmac('sha256', http_build_query($data), $this->apiKey);

        $response = $this->httpClient->request(
            'GET',
            '/search?' . http_build_query($data)
        );

        if ($response->status >= 400) {
            $message = null;

            if (($error = json_decode($response->body)) && isset($error->message)) {
                $message = $error->message;
            }
            throw new \RuntimeException("Search failed: " . $message);
        }

        if (!$response->body || !($return = json_decode($response->body, true))) {
            throw new \RuntimeException("Response could not be processed: " . $response->body);
        }

        $result = new Struct\SearchResult($return);
        $result->search = $search;
        $result->results = array_map(
            function ($product) {
                return new Struct\SearchResult\Product($product);
            },
            $result->results
        );

        return $result;
    }
}
