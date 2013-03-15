<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * @version 1.0.0snapshot201303151129
 */

namespace Bepado\SDK\Logger;

use Bepado\SDK\Logger;
use Bepado\SDK\HttpClient;
use Bepado\SDK\Struct;

/**
 * Base class for logger implementations
 *
 * @version 1.0.0snapshot201303151129
 */
class Http extends Logger
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
     * Log order
     *
     * @param Struct\Order $order
     * @return void
     */
    protected function doLog(Struct\Order $order)
    {
        $response = $this->httpClient->request(
            'POST',
            '/transaction',
            json_encode($order),
            array(
                'Content-Type: application/json',
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
