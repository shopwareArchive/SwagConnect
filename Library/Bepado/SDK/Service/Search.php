<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * @version 1.0.0snapshot201303151129
 */

namespace Bepado\SDK\Service;

use Bepado\SDK\HttpClient;
use Bepado\SDK\Struct;

/**
 * Search service
 *
 * @version 1.0.0snapshot201303151129
 */
class Search
{
    /**
     * HTTP Client
     *
     * @var HttpClient
     */
    protected $httpClient;

    public function __construct(
        HttpClient $httpClient
    ) {
        $this->httpClient = $httpClient;
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
        $response = $this->httpClient->request(
            'GET',
            '/search?' . http_build_query((array) $search)
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
