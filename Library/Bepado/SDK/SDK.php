<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Bepado\SDK;

use Bepado\SDK\Struct\RpcCall;
use Bepado\SDK\Struct\Shop;

/**
 * Central SDK class, which serves as an etnry point and service fromShop.
 *
 * Register your gateway and product handlers here. All calls should be
 * dispatched to this class. It constructs the required helper classes as
 * required.
 *
 * NOTICE: Use the \Bepado\SDK\SDKBuilder to create an instance of the SDK.
 * This handles all the complexity of creating the different dependencies.
 * The constructor may change in the future, using the SDKBuilder is
 * required to implement a supported plugin.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 * @api
 */
final class SDK
{
    /**
     * API key for this SDK
     *
     * @var string
     */
    private $apiKey;

    /**
     * API endpoint URL for this SDK
     *
     * @var string
     */
    private $apiEndpointUrl;

    /**
     * Dependency resolver for SDK dependencies
     *
     * @var DependencyResolver
     */
    private $dependencies;

    /**
     * Version constant
     */
    const VERSION = '$Revision$';

    /**
     * @param string $apiKey API key assigned to you by Bepado
     * @param string $apiEndpointUrl Your local API endpoint
     * @param \Bepado\SDK\Gateway $gateway
     * @param \Bepado\SDK\ProductToShop $toShop
     * @param \Bepado\SDK\ProductFromShop $fromShop
     * @param \Bepado\SDK\ErrorHandler $errorHandler
     * @param \Bepado\SDK\HttpClient\RequestSigner $requestSigner
     * @param string $pluginSoftwareVersion
     */
    public function __construct(
        $apiKey,
        $apiEndpointUrl,
        Gateway $gateway,
        ProductToShop $toShop,
        ProductFromShop $fromShop,
        ErrorHandler $errorHandler = null,
        HttpClient\RequestSigner $requestSigner = null,
        $pluginSoftwareVersion = null
    ) {
        $this->apiKey = $apiKey;
        $this->apiEndpointUrl = $apiEndpointUrl;

        // The dependencies are not supposed to be injected. This is an
        // entirely pre-configured object, except for the properties available
        // through constructor injection. Dependency Injection is only used
        // internally in the SDK.
        $this->dependencies = new DependencyResolver(
            $gateway,
            $toShop,
            $fromShop,
            $errorHandler ? $errorHandler : new ErrorHandler\Exception(),
            $apiKey,
            $requestSigner,
            $pluginSoftwareVersion
        );
    }

    /**
     * Check if the SDK was verified before successfully.
     *
     * @return bool
     */
    public function isVerified()
    {
        return $this->dependencies->getVerificationService()->isVerified();
    }

    /**
     * Tries to verify this SDK, if this did not happen yet.
     *
     * Throws an exception if the verification failed and the required data
     * could not be retrieved or verified.
     *
     * @throws \DomainException
     * @return void
     */
    public function verifySdk()
    {
        $this->dependencies->getVerificationService()->verify(
            $this->apiKey,
            $this->apiEndpointUrl
        );
    }

    private function verifySdkIfNecessary()
    {
        if (!$this->isVerified()) {
            $this->verifySdk();
        }
    }

    /**
     * Handle request XML
     *
     * handle the XML encoding the web service request. Returns XML building
     * the response.
     *
     * @param string $xml
     * @param array $headers
     *
     * @return string
     */
    public function handle($xml, array $headers = null)
    {
        if ($this->isPingRequest($headers)) {
            return $this->generatePongResponse();
        }

        $this->verifySdkIfNecessary();
        $token = $this->verifyRequest($xml, $headers);

        $serviceRegistry = $this->dependencies->getServiceRegistry();

        if ($token->userIdentifier) {
            $serviceRegistry = new ServiceRegistry\Authorization($serviceRegistry, $token);
        }

        return $this->dependencies->getMarshaller()->marshal(
            new RpcCall(
                array(
                    'service' => 'null',
                    'command' => 'return',
                    'arguments' => array(
                        $serviceRegistry->dispatch(
                            $this->dependencies->getUnmarshaller()->unmarshal($xml)
                        ),
                    )
                )
            )
        );
    }

    /**
     * @param array $headers
     * @return bool
     */
    private function isPingRequest(array $headers = null)
    {
        return ($headers !== null && isset($headers['HTTP_X_BEPADO_PING'])
            || $headers === null && isset($_SERVER['HTTP_X_BEPADO_PING']));
    }

    /**
     * @return string
     */
    private function generatePongResponse()
    {
        return '<?xml version="1.0" encoding="utf-8"?>'. "\n"
            . '<pong/>';
    }

    /**
     * Check Authentication of the Request
     *
     * @return AuthenticationToken
     */
    private function verifyRequest($body, array $headers = null)
    {
        if ($headers === null) {
            $headers = array();

            foreach ($_SERVER as $name => $value) {
                if (strpos($name, "HTTP_") === 0) {
                    $headers[$name] = $value;
                }
            }
        }

        $requestSigner = $this->dependencies->getRequestSigner();
        $token = $requestSigner->verifyRequest($body, $headers);

        if (false === $token->authenticated) {
            throw new SecurityException(
                sprintf(
                    "Authorization of RPC request failed for user '%s' to shop '%d'. (Reason: %s)",
                    $token->userIdentifier,
                    $this->dependencies->getGateway()->getShopId(),
                    $token->errorMessage
                )
            );
        }

        return $token;
    }

    /**
     * Sync changes feed
     *
     * Evaluates which products are new in the shop database and marks those
     * products for the export. Results in new inserts, updates and deletes in
     * the changes feed.
     *
     * Use this method, if your shop is not able to record all change
     * operations on your products itself, using the record*() methods.
     *
     * This operation can be really expensive to execute, depending on the
     * number of products you export.
     *
     * @return void
     */
    public function recreateChangesFeed()
    {
        $this->verifySdkIfNecessary();
        $this->dependencies->getSyncService()->recreateChangesFeed();
    }

    /**
     * Record product insert
     *
     * Establish a hook in your shop and call this method for every new
     * product, which should be exported to Bepado.
     *
     * @param string $productId
     * @return void
     */
    public function recordInsert($productId)
    {
        $this->verifySdkIfNecessary();

        $product = $this->getProduct($productId);
        $product->shopId = $this->dependencies->getGateway()->getShopId();

        $this->dependencies->getVerificator()->verify($product);
        $this->dependencies->getGateway()->recordInsert(
            $product->sourceId,
            $this->dependencies->getProductHasher()->hash($product),
            $this->dependencies->getRevisionProvider()->next(),
            $product
        );
    }

    /**
     * Record product update
     *
     * Establish a hook in your shop and call this method for every update of a
     * product, which is exported to Bepado.
     *
     * @param string $productId
     * @return void
     */
    public function recordUpdate($productId)
    {
        $this->verifySdkIfNecessary();

        $product = $this->getProduct($productId);
        $product->shopId = $this->dependencies->getGateway()->getShopId();

        $this->dependencies->getVerificator()->verify($product);
        $this->dependencies->getGateway()->recordUpdate(
            $product->sourceId,
            $this->dependencies->getProductHasher()->hash($product),
            $this->dependencies->getRevisionProvider()->next(),
            $product
        );
    }

    /**
     * Record product availability update
     *
     * Establish a hook in your shop and call this method for every update of a
     * product availability, which is exported to Bepado.
     *
     * @param string $productId
     * @return void
     */
    public function recordAvailabilityUpdate($productId)
    {
        $this->verifySdkIfNecessary();

        $product = $this->getProduct($productId);
        $product->shopId = $this->dependencies->getGateway()->getShopId();

        $this->dependencies->getVerificator()->verify($product);
        $this->dependencies->getGateway()->recordAvailabilityUpdate(
            $product->sourceId,
            $this->dependencies->getProductHasher()->hash($product),
            $this->dependencies->getRevisionProvider()->next(),
            $product
        );
    }

    /**
     * Get single product from gateway
     *
     * @param mixed $productId
     * @return Struct\Product
     */
    protected function getProduct($productId)
    {
        $products = $this->dependencies->getFromShop()->getProducts(array($productId));
        return reset($products);
    }

    /**
     * Record product delete
     *
     * Establish a hook in your shop and call this method for every delete of a
     * product, which is exported to Bepado.
     *
     * @param string $productId
     * @return void
     */
    public function recordDelete($productId)
    {
        $this->verifySdkIfNecessary();
        $this->dependencies->getGateway()->recordDelete($productId, $this->dependencies->getRevisionProvider()->next());
    }

    /**
     * Calculate shipping costs
     *
     * Calculate shipping costs for the given set of products.
     *
     * @param Struct\Order $order
     * @return Struct\TotalShippingCosts
     */
    public function calculateShippingCosts(Struct\Order $order)
    {
        $this->verifySdkIfNecessary();

        foreach ($order->orderItems as $orderItem) {
            $this->dependencies->getVerificator()->verify($orderItem);
        }

        return $this->dependencies->getShoppingService()->calculateShippingCosts(
            $order,
            Gateway\ShippingCosts::SHIPPING_COSTS_CUSTOMER
        );
    }

    /**
     * Check products still are in the state they are stored locally
     *
     * This method will verify with the remote shops that products are still in
     * the expected state. If the state of products changed this method will
     * return a Struct\Message, which should be ACK'ed by the user. Otherwise
     * this method will just return true.
     *
     * If data updated are detected, the local product database will be updated
     * accordingly.
     *
     * This method is a convenience method to check the state of a set of
     * remote products. The state will be checked again during
     * reserveProducts().
     *
     * @param Struct\Product[] $products
     * @return mixed
     */
    public function checkProducts(array $products)
    {
        $this->verifySdkIfNecessary();

        $productList = new Struct\ProductList(array('products' => $products));

        $this->dependencies->getVerificator()->verify($productList);

        return $this->dependencies->getShoppingService()->checkProducts($productList);
    }

    /**
     * Reserve products
     *
     * This method will reserve the given products in the remote shops. It
     * should be called at the beginning of the checkout process. It is the
     * last chance to verify that everything is OK with the order.
     *
     * If the product data change in a relevant way, this method will not
     * reserve the products, but instead the messages property will contain
     * messages, which should be displayed to the user and the success property
     * will be false. The messages should be ACK'ed by the user. Afterwards
     * another reservation may be issued.
     *
     * If The reservation of the product set succeeded the orders in the
     * reservation struct will have a reservationID set. The reservation struct
     * should be sored in the shop for the checkout process. The session is
     * probably the best location for this.
     *
     * If data updated are detected, the local product database will be updated
     * accordingly.
     *
     * @param Struct\Order $order
     * @return Struct\Reservation
     */
    public function reserveProducts(Struct\Order $order)
    {
        $this->verifySdkIfNecessary();

        $order->orderShop = $this->dependencies->getGateway()->getShopId();
        $order->billingAddress = $this->dependencies->getGateway()->getBillingAddress();

        $this->dependencies->getVerificator()->verify($order);

        return $this->dependencies->getShoppingService()->reserveProducts($order);
    }

    /**
     * Checkout product sets related to the given reservation IDs
     *
     * This process is the final "buy" transaction. It should be the last step
     * of the checkout process and be handled synchronously. Is supposed to
     * "always" succeed, since the reservation beforhand should already ensure
     * everything is fine.
     *
     * This method will just return true, if the transaction worked as
     * expected. If it failed, or partially failed, an error will be logged
     * with the ErrorHandler and the method will return false.
     *
     * @param Struct\Reservation $reservation
     * @param string $orderId
     * @return bool[]
     */
    public function checkout(Struct\Reservation $reservation, $orderId)
    {
        $this->verifySdkIfNecessary();

        if (!$reservation->success ||
            count($reservation->messages) ||
            !array_reduce(
                array_map(
                    function (Struct\Order $order) {
                        return $order->reservationId;
                    },
                    $reservation->orders
                ),
                function ($old, $reservationID) {
                    return $old && $reservationID;
                },
                true
            )) {
            throw new \RuntimeException("Invalid reservation provided.");
        }

        return $this->dependencies->getShoppingService()->checkout($reservation, $orderId);
    }

    /**
     * Perform search on Bepado
     *
     * Search will return a SearchResult struct, which can be used to display
     * the search results in your shop. For details on the Search and
     * SearchResult structs see the respective API documentation.
     *
     * @param Struct\Search $search
     * @return Struct\SearchResult
     */
    public function search(Struct\Search $search)
    {
        $this->verifySdkIfNecessary();

        return $this->dependencies->getSearchService()->search($search);
    }

    /**
     * Return array with categories
     *
     * The array is indexed by the category identifiers, which should be used
     * to reference categories in products.
     *
     * The values of the arary are the category names, which can be used to
     * provided users an overview of the Bepado categories.
     *
     * @return array
     */
    public function getCategories()
    {
        $this->verifySdkIfNecessary();
        return $this->dependencies->getGateway()->getCategories();
    }

    /**
     * Verify a given API key is valid.
     *
     * @param $key
     */
    public function verifyKey($key)
    {
        $this->dependencies->getVerificationService()->verify(
            $key,
            $this->apiEndpointUrl
        );
    }

    /**
     * Get information about a shop given its remote shop-id.
     *
     * This method allows access to the shop name - which can be
     * used for UI purposes.
     *
     * @param string $shopId
     * @return \Bepado\SDK\Struct\Shop
     */
    public function getShop($shopId)
    {
        $shopConfiguration = $this->dependencies->getGateway()->getShopConfiguration($shopId);

        return new Shop(
            array(
                'id' => $shopId,
                'name' => $shopConfiguration->displayName,
                'url' => $shopConfiguration->url,
            )
        );
    }

    /**
     * Update the status of a bepado order.
     *
     * @param \Bepado\SDK\Struct\OrderStatus $status
     *
     * @return void
     */
    public function updateOrderStatus(Struct\OrderStatus $status)
    {
        $this->verifySdkIfNecessary();

        $this->dependencies->getSocialNetworkService()->updateOrderStatus($status);
    }

    /**
     * Request unsubscribe of product subscriptions
     *
     * @param \Bepado\SDK\Struct\ProductId[]
     * @return void
     */
    public function unsubscribeProducts(array $productIds)
    {
        $this->verifySdkIfNecessary();

        $this->dependencies->getSocialNetworkService()->unsubscribeProducts($productIds);
    }

    /**
     * Return all available end customer shipping cost rules.
     *
     * Useful to dynamically generate a shipping cost page in the shop.
     *
     * @return array<string, \Bepado\SDK\ShippingCosts\Rules>
     */
    public function getShippingCostRules()
    {
        $gateway = $this->dependencies->getGateway();
        $service = $this->dependencies->getShippingCostsService();

        $shopId = $gateway->getShopId();
        $connectedShopIds = $gateway->getConnectedShopIds();

        $rules = array();

        foreach ($connectedShopIds as $connectedShopId) {
            $rules[$connectedShopId] = $service->getShippingCostRules(
                $connectedShopId,
                $shopId,
                Gateway\ShippingCosts::SHIPPING_COSTS_CUSTOMER
            );
        }

        return $rules;
    }

    /**
     * Return time in seconds needed to finish synchronization
     *
     * @param $changesCount
     * @return int
     */
    public function calculateFinishTime($changesCount)
    {
        return $this->dependencies->getSocialNetworkService()->calculateFinishTime($changesCount);
    }


    /**
     * Returns array with available marketplace product attributes
     * as attribute => label pairs
     *
     * @return array
     */
    public function getMarketplaceProductAttributes()
    {
        return $this->dependencies->getSocialNetworkService()->getMarketplaceProductAttributes();
    }


    /**
     * Returns array with marketplace settings
     * as key => value
     * @return array
     */
    public function getMarketplaceSettings()
    {
        return $this->dependencies->getSocialNetworkService()->getMarketplaceSettings();
    }
}
