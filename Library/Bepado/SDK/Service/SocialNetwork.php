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
use Bepado\SDK\Struct\ProductId;

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

        $response = $this->request('/sdk/update-order-status', $orderStatus);
        $this->handleResponse($response, "Order status update");
    }

    /**
     * Notify SocialNetwork to unsubscribe the given set of products.
     *
     * The Updater will then push those deletions back to the shop at some
     * point in the future.
     *
     * @param \Bepado\SDK\Struct\ProductId[]
     *
     * @return void
     */
    public function unsubscribeProducts(array $productIds)
    {
        $this->verifyProductIds($productIds);

        $response = $this->request('/sdk/unsubscribe-products', $productIds);
        $this->handleResponse($response, "Unsubscribe products");
    }

    private function verifyProductIds(array $productIds)
    {
        foreach ($productIds as $productId) {
            if (!($productId instanceof ProductId)) {
                throw new \RuntimeException("No \Bepado\SDK\Struct\ProductId given.");
            }
        }
    }

    private function request($url, $data)
    {
        $payload = json_encode($data);
        $key = hash_hmac('sha512', $payload, $this->apiKey);

        return $this->httpClient->request(
            'POST',
            $url,
            $payload,
            array(
                'Content-Type: application/json',
                'X-Bepado-Shop: ' . $this->shopId,
                'X-Bepado-Key: ' . $key,
            )
        );
    }

    private function handleResponse($response, $op)
    {
        if ($response->status >= 400) {
            $message = null;
            if (($error = json_decode($response->body)) &&
                isset($error->message)) {
                $message = $error->message;
            }
            throw new \RuntimeException($op . " failed: " . $message);
        }
    }
}
