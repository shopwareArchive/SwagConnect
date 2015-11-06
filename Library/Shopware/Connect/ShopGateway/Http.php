<?php
/**
 * This file is part of the Shopware Connect SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Shopware\Connect\ShopGateway;

use Shopware\Connect\ShopGateway;
use Shopware\Connect\Struct;
use Shopware\Connect\HttpClient;
use Shopware\Connect\Rpc\Marshaller\CallMarshaller;
use Shopware\Connect\Rpc\Marshaller\CallUnmarshaller;
use Shopware\Connect\Struct\RpcCall;

/**
 * Shop gateway HTTP implementation
 *
 * Gateway to interact with other shops
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */
class Http extends ShopGateway
{
    /**
     * HTTP Client
     *
     * @var HttpClient
     */
    protected $httpClient;

    /**
     * Call marshaller
     *
     * @var Rpc\Marshaller\CallMarshaller
     */
    protected $marshaller;

    /**
     * Call unmarshaller
     *
     * @var Rpc\Marshaller\CallUnmarshaller
     */
    protected $unmarshaller;

    /**
     * @var ShopRequestSigner
     */
    protected $shopRequestSigner;

    /**
     * @param Shopware\Connect\HttpClient $httpClient
     * @param Shopware\Connect\Rpc\Marshaller\CallMarshaller $marshaller
     * @param Shopware\Connect\Rpc\Marshaller\CallUnmarshaller $unmarshaller
     * @param Shopware\Connect\Gateway\ShopConfiguration $providerShopConfig
     * @param Shopware\Connect\ShopGateway\ShopRequestSigner $shopRequestSigner
     */
    public function __construct(
        HttpClient $httpClient,
        CallMarshaller $marshaller,
        CallUnmarshaller $unmarshaller,
        ShopRequestSigner $shopRequestSigner
    ) {
        $this->httpClient = $httpClient;
        $this->marshaller = $marshaller;
        $this->unmarshaller = $unmarshaller;
        $this->shopRequestSigner = $shopRequestSigner;
    }

    /**
     * Check order in shop
     *
     * Verifies, if all products in the given list still have the same price
     * and availability as in the remote shop..
     *
     * Returns true on success, or an array of Struct\Change with updates for
     * the requested products.
     *
     * @param Struct\Order $order
     * @param string $shopId
     * @return mixed
     */
    public function checkProducts(Struct\Order $order, $shopId)
    {
        return $this->makeRpcCall(
            new RpcCall(
                array(
                    'service' => 'transaction',
                    'command' => 'checkProducts',
                    'arguments' => array($order, $shopId),
                )
            )
        );
    }

    /**
     * Reserve order in remote shop
     *
     * Products SHOULD be reserved and not be sold out while bing reserved.
     * Reservation may be cancelled after sufficient time has passed.
     *
     * Returns a reservationId on success, or an array of Struct\Change with
     * updates for the requested products.
     *
     * @param Struct\Order
     * @return mixed
     */
    public function reserveProducts(Struct\Order $order)
    {
        return $this->makeRpcCall(
            new RpcCall(
                array(
                    'service' => 'transaction',
                    'command' => 'reserveProducts',
                    'arguments' => array($order),
                )
            )
        );
    }

    /**
     * Buy order associated with reservation in the remote shop.
     *
     * Returns true on success, or a Struct\Message on failure. SHOULD never
     * fail.
     *
     * @param string $reservationId
     * @param string $orderId
     * @return mixed
     */
    public function buy($reservationId, $orderId)
    {
        return $this->makeRpcCall(
            new RpcCall(
                array(
                    'service' => 'transaction',
                    'command' => 'buy',
                    'arguments' => array($reservationId, $orderId),
                )
            )
        );
    }

    /**
     * Confirm a reservation in the remote shop.
     *
     * Returns true on success, or a Struct\Message on failure. SHOULD never
     * fail.
     *
     * @param string $reservationId
     * @param string $remoteLogTransactionId
     * @return mixed
     */
    public function confirm($reservationId, $remoteLogTransactionId)
    {
        return $this->makeRpcCall(
            new RpcCall(
                array(
                    'service' => 'transaction',
                    'command' => 'confirm',
                    'arguments' => array($reservationId, $remoteLogTransactionId),
                )
            )
        );
    }

    /**
     * Performs the given $call to the provider shop
     *
     * @param Shopware\Connect\Struct\RpcCall $call
     * @return Shopware\Connect\Struct\RpcCall Returned call
     */
    protected function makeRpcCall(RpcCall $call)
    {
        $marshalledCall = $this->marshaller->marshal($call);
        $signHeaders = $this->shopRequestSigner->signRequest($marshalledCall);

        try {
            $httpResponse = $this->httpClient->request(
                'POST',
                '',
                $marshalledCall,
                $signHeaders
            );

            $result = $this->unmarshaller->unmarshal($httpResponse->body);
            if (!isset($result->arguments[0])) {
                throw new \UnexpectedValueException("Could not parse response: " . $httpResponse->body);
            }
        } catch (\Exception $e) {
            return new Struct\Error(
                array(
                    'message' => $e->getMessage(),
                    'debugText' => (string) $e,
                )
            );
        }

        return $result->arguments[0]->result;
    }
}
