<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * @version $Revision$
 */

namespace Bepado\SDK\ShopGateway;

use Bepado\SDK\ShopGateway;
use Bepado\SDK\Struct;
use Bepado\SDK\HttpClient;
use Bepado\Common\Rpc\Marshaller\CallMarshaller;
use Bepado\Common\Rpc\Marshaller\CallUnmarshaller;
use Bepado\Common\Struct\RpcCall;

/**
 * Shop gateway HTTP implementation
 *
 * Gateway to interact with other shops
 *
 * @version $Revision$
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
     * @param Bepado\SDK\HttpClient $httpClient
     * @param Bepado\Common\Rpc\Marshaller\CallMarshaller $marshaller
     * @param Bepado\Common\Rpc\Marshaller\CallUnmarshaller $unmarshaller
     * @param Bepado\SDK\Gateway\ShopConfiguration $providerShopConfig
     */
    public function __construct(HttpClient $httpClient, CallMarshaller $marshaller, CallUnmarshaller $unmarshaller)
    {
        $this->httpClient = $httpClient;
        $this->marshaller = $marshaller;
        $this->unmarshaller = $unmarshaller;
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
     * @param Struct\ProductList $productList
     * @return mixed
     */
    public function checkProducts(Struct\ProductList $productList)
    {
        return $this->makeRpcCall(
            new RpcCall(
                array(
                    'service' => 'transaction',
                    'command' => 'checkProducts',
                    'arguments' => array($productList),
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
     * @param Bepado\Command\Struct\RpcCall $call
     * @return Bepado\Command\Struct\RpcCall Returned call
     */
    protected function makeRpcCall(RpcCall $call)
    {
        $marshalledCall = $this->marshaller->marshal($call);

        $httpResponse = $this->httpClient->request(
            'POST',
            '',
            $marshalledCall
        );

        // TODO: Check status
        $result = $this->unmarshaller->unmarshal($httpResponse->body);

        return $result->arguments[0]->result;
    }
}
