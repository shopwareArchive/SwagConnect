<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * @version $Revision$
 */

namespace Bepado\SDK\Logger;

use Bepado\SDK\Logger;
use Bepado\SDK\HttpClient;
use Bepado\SDK\Struct;

/**
 * Base class for logger implementations
 *
 * @version $Revision$
 */
class Http extends Logger
{
    /**
     * HTTP Client
     *
     * @var HttpClient
     */
    protected $httpClient;

    /**
     * API Key
     *
     * @var string
     */
    protected $apiKey;

    public function __construct(
        HttpClient $httpClient,
        $apiKey
    ) {
        $this->httpClient = $httpClient;
        $this->apiKey = $apiKey;
    }

    /**
     * Log order
     *
     * @param Struct\Order $order
     * @return void
     */
    protected function doLog(Struct\Order $order)
    {
        $hash = hash_hmac("sha256", $order->localOrderId . $order->orderShop . $order->providerShop, $this->apiKey);

        $response = $this->httpClient->request(
            'POST',
            '/transaction',
            json_encode($order),
            array(
                'Content-Type: application/json',
                'X-Bepado-Order-Hash: ' . $hash
            )
        );

        if ($response->status >= 400) {
            $message = null;
            if ($error = json_decode($response->body)) {
                $message = $error->message;
            }
            throw new \RuntimeException("Logging failed: " . $message);
        }

        return;
    }
}
