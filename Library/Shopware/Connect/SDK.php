<?php
/**
 * This file is part of the Shopware Connect SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Shopware\Connect;

use Shopware\Connect\Struct\RpcCall;
use Shopware\Connect\Struct\Shop;

/**
 * Central SDK class, which serves as an etnry point and service fromShop.
 *
 * Register your gateway and product handlers here. All calls should be
 * dispatched to this class. It constructs the required helper classes as
 * required.
 *
 * NOTICE: Use the \Shopware\Connect\SDKBuilder to create an instance of the SDK.
 * This handles all the complexity of creating the different dependencies.
 * The constructor may change in the future, using the SDKBuilder is
 * required to implement a supported plugin.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 * @api
 */
final class SDK
{
    const CONFIG_PRICE_TYPE = '_price_type';
    const PRICE_TYPE_PURCHASE = 1;
    const PRICE_TYPE_RETAIL = 2;
    const PRICE_TYPE_BOTH = 3;
    const PRICE_TYPE_NONE = 4;

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
    const VERSION = '2.0.1';

    /**
     * @param string $apiKey API key assigned to you by Shopware Connect
     * @param string $apiEndpointUrl Your local API endpoint
     * @param \Shopware\Connect\Gateway $gateway
     * @param \Shopware\Connect\ProductToShop $toShop
     * @param \Shopware\Connect\ProductFromShop $fromShop
     * @param \Shopware\Connect\ErrorHandler $errorHandler
     * @param \Shopware\Connect\HttpClient\RequestSigner $requestSigner
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
        return ($headers !== null && isset($headers['HTTP_X_SHOPWARE_CONNECT_PING'])
            || $headers === null && isset($_SERVER['HTTP_X_SHOPWARE_CONNECT_PING']));
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
     * product, which should be exported to Shopware Connect.
     *
     * @param string $productId
     * @return void
     */
    public function recordInsert($productId)
    {
        $this->verifySdkIfNecessary();

        $this->dependencies->getExportService()->recordInsert($productId);
    }

    /**
     * Record product update
     *
     * Establish a hook in your shop and call this method for every update of a
     * product, which is exported to Shopware Connect.
     *
     * @param string $productId
     * @return void
     */
    public function recordUpdate($productId)
    {
        $this->verifySdkIfNecessary();

        $this->dependencies->getExportService()->recordUpdate($productId);
    }

    /**
     * Record product availability update
     *
     * Establish a hook in your shop and call this method for every update of a
     * product availability, which is exported to Shopware Connect.
     *
     * @param string $productId
     * @return void
     */
    public function recordAvailabilityUpdate($productId)
    {
        $this->verifySdkIfNecessary();

        $this->dependencies->getExportService()->recordAvailabilityUpdate($productId);
    }

    /**
     * Record product delete
     *
     * Establish a hook in your shop and call this method for every delete of a
     * product, which is exported to Shopware Connect.
     *
     * @param string $productId
     * @return void
     */
    public function recordDelete($productId)
    {
        $this->verifySdkIfNecessary();

        $this->dependencies->getExportService()->recordDelete($productId);
    }

    public function recordStreamAssignment($productId, array $supplierStreams)
    {
        $this->verifySdkIfNecessary();

        $this->dependencies->getExportService()->recordStreamAssignment($productId, $supplierStreams);
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
     * @param Struct\Order $order
     * @return Struct\CheckResult
     */
    public function checkProducts(Struct\Order $order)
    {
        $this->verifySdkIfNecessary();
        $this->dependencies->getVerificator()->verify($order);

        return $this->dependencies->getShoppingService()->checkProducts($order);
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
     * @return \Shopware\Connect\Struct\Shop
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
     * @param \Shopware\Connect\Struct\OrderStatus $status
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
     * @param \Shopware\Connect\Struct\ProductId[]
     * @return void
     */
    public function unsubscribeProducts(array $productIds)
    {
        $this->verifySdkIfNecessary();

        $this->dependencies->getSocialNetworkService()->unsubscribeProducts($productIds);
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

    /**
     * The price type determines which prices must be exported to bepado.
     *
     * Options are:
     *
     * - SDK::PRICE_TYPE_PURCHASE: Only the purchase price of a product is exported.
     * - SDK::PRICE_TYPE_RETAIL: Only the retail price (Product#price) is exported.
     * - SDK::PRICE_TYPE_BOTH: Both purchase and retail price are exported.
     * - SDK::PRICE_TYPE_NONE: No product export, UI can hide all related operations and config screens.
     *
     * The SDK handles the price exporting internally, this getter can be used
     * for plugin authors to improve the User Interface for configuration of the
     * bepado prices.
     *
     * @return int
     */
    public function getPriceType()
    {
        $priceType = $this->dependencies->getGateway()->getConfig(SDK::CONFIG_PRICE_TYPE);

        if (!$priceType) {
            return self::PRICE_TYPE_NONE;
        }

        return (int)$priceType;
    }

    public function setPriceType($priceType)
    {
        $priceType = (int)$priceType;
        $availablePriceTypes = [self::PRICE_TYPE_RETAIL, self::PRICE_TYPE_PURCHASE, self::PRICE_TYPE_BOTH];
        if (!in_array($priceType, $availablePriceTypes)) {
            throw new \InvalidArgumentException(sprintf(
                'Price type %s is not supported, please check Shopware\Connect\SDK for available values.',
                $priceType
            ));
        }

        $result = $this->dependencies->getSocialNetworkService()->setPriceType($priceType);
        if ($result) {
            $this->dependencies->getGateway()->setConfig(self::CONFIG_PRICE_TYPE, $priceType);
        }
    }
}
