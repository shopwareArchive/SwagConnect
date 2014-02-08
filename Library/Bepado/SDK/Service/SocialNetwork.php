<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * @version $Revision$
 */

namespace Bepado\SDK\Service;

use Bepado\SDK\Struct\VerificatorDispatcher;
use Bepado\SDK\Gateway;
use Bepado\SDK\HttpClient;
use Bepado\SDK\Struct\OrderStatus;

/**
 * Allows updating the status of orders for provider shops,
 * making the Status visible in Bepado.
 */
class SocialNetwork
{
    /**
     * HTTP Client
     *
     * @var HttpClient
     */
    protected $httpClient;

    /**
     * @var integer
     */
    protected $shopId;

    /**
     * The Bepado Api-Key
     *
     * @var string
     */
    protected $apiKey;

    /**
     * @var \Bepado\SDK\Struct\VerificatorDispatcher
     */
    protected $verificator;

    public function __construct(HttpClient $httpClient, VerificatorDispatcher $verificator, $shopId, $apiKey)
    {
        $this->httpClient = $httpClient;
        $this->apiKey = $apiKey;
        $this->shopId = $shopId;
        $this->verificator = $verificator;
    }

    /**
     * Update the status of a remote Bepado order using your local order id.
     *
     * Status can be one of 'open', 'in_process', 'delivered', 'canceled', 'error'.
     * Bepado will update the order shop of this change.
     *
     * @param \Bepado\SDK\Struct\OrderStatus
     *
     * @return void
     */
    public function updateOrderStatus(OrderStatus $orderStatus)
    {
        $this->verificator->verify($orderStatus);

        $data = json_encode($orderStatus);
        $key = hash_hmac('sha512', $data, $this->apiKey);

        $response = $this->httpClient->request(
            'POST',
            '/sdk/update-order-status',
            $data,
            array(
                'Content-Type: application/json',
                'X-Bepado-Shop: ' . $this->shopId,
                'X-Bepado-Key: ' . $key,
            )
        );

        if ($response->status >= 400) {
            echo($response->body);
            $message = null;
            if (($error = json_decode($response->body)) &&
                isset($error->message)) {
                $message = $error->message;
            }
            throw new \RuntimeException("Order status update failed: " . $message);
        }
    }
}
