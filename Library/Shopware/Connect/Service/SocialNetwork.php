<?php
/**
 * This file is part of the Shopware Connect SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Shopware\Connect\Service;

use Shopware\Connect\Struct\VerificatorDispatcher;
use Shopware\Connect\Gateway;
use Shopware\Connect\HttpClient;
use Shopware\Connect\Struct;

/**
 * Allows updating the status of orders for provider shops,
 * making the Status visible in Shopware Connect.
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
     * The Shopware Connect Api-Key
     *
     * @var string
     */
    protected $apiKey;

    /**
     * @var \Shopware\Connect\Struct\VerificatorDispatcher
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
     * Update the status of a remote Shopware Connect order using your local order id.
     *
     * Status can be one of 'open', 'in_process', 'delivered', 'canceled', 'error'.
     * Shopware Connect will update the order shop of this change.
     *
     * @param \Shopware\Connect\Struct\OrderStatus
     *
     * @return void
     */
    public function updateOrderStatus(Struct\OrderStatus $orderStatus)
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
     * @param \Shopware\Connect\Struct\ProductId[]
     *
     * @return void
     */
    public function unsubscribeProducts(array $productIds)
    {
        $this->verifyProductIds($productIds);

        $response = $this->request('/sdk/unsubscribe-products', $productIds);
        $this->handleResponse($response, "Unsubscribe products");
    }

    /**
     * Ask SocialNetwork how long Shopware Connect will synchronize changes
     *
     * @param $changesCount
     */
    public function calculateFinishTime($changesCount)
    {
        $response = $this->request('/sdk/calculate-finish-time', array('count' => $changesCount));
        $this->handleResponse($response, "Calculate finish time");

        $responseBody = json_decode($response->body);
        return $responseBody->time;
    }

    /**
     * Returns array with available marketplace product attributes
     * as attribute => label pairs
     *
     * @return array
     */
    public function getMarketplaceProductAttributes()
    {
        $response = $this->request('/sdk/marketplace/attributes', array());
        $this->handleResponse($response, "Marketplace product attributes");

        $responseBody = json_decode($response->body, true);
        return $responseBody['attributes'];
    }

    /**
     * Returns array with available marketplace settings
     * as key => value pairs
     *
     * @return array
     */
    public function getMarketplaceSettings()
    {
        $response = $this->request('/sdk/marketplace/settings', array());
        $this->handleResponse($response, "Marketplace settings");

        $responseBody = json_decode($response->body, true);
        return $responseBody['settings'];
    }

    /**
     * @return \Shopware\Connect\Struct\Shipping
     */
    public function calculateShippingCosts(Struct\Order $order)
    {
        $response = $this->request('/sdk/shipping-costs', array("order" => $order));

        $this->handleResponse($response, "Shipping Costs");

        $body = json_decode($response->body,true);
        $shipping = $body['shipping'];

        return new Struct\Shipping($shipping);
    }

    private function verifyProductIds(array $productIds)
    {
        foreach ($productIds as $productId) {
            if (!($productId instanceof Struct\ProductId)) {
                throw new \RuntimeException("No \Shopware\Connect\Struct\ProductId given.");
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
                'X-Shopware-Connect-Shop: ' . $this->shopId,
                'X-Shopware-Connect-Key: ' . $key,
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
