<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * @version $Revision$
 */

namespace Bepado\SDK\Service;

use Bepado\SDK\Gateway;
use Bepado\SDK\HttpClient;

/**
 * Allows updating the status of orders for provider shops,
 * making the Status visible in Bepado.
 */
class OrderStatusUpdate
{
    /**
     * HTTP Client
     *
     * @var HttpClient
     */
    protected $httpClient;

    /**
     * The Bepado Api-Key
     *
     * @var string
     */
    protected $apiKey;

    public function __construct(HttpClient $httpClient, $apiKey)
    {
        $this->httpClient = $httpClient;
        $this->apiKey = $apiKey;
    }

    /**
     * Update the status of a remote Bepado order using your local order id.
     *
     * Status can be one of 'open', 'in_process', 'delivered', 'canceled', 'error'.
     * Bepado will update the order shop of this change.
     *
     * @param int $orderId
     * @param string $status
     * @param \Bepado\SDK\Struct\Message[] $messages
     *
     * @return void
     */
    public function update($orderId, $status, array $messages = array())
    {
        $allowedStates = array('open', 'in_process', 'delivered', 'canceled', 'error');

        if (!in_array($status, $allowedStates)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Invalid order state given: %s. Expected one of: %s',
                    $status,
                    implode(', ', $allowedStates)
                )
            );
        }

        $response = $this->httpClient->request(
            'POST',
            '/sdk/update-order-status',
            json_encode(
                array(
                    'apiKey' => $this->apiKey,
                    'remoteOrderId' => $orderId,
                    'orderStatus' => $status,
                    'messages' => $messages,
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
            throw new \RuntimeException("Order status update failed: " . $message);
        }
    }
}
