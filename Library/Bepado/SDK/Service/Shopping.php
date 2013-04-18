<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * @version $Revision$
 */

namespace Bepado\SDK\Service;

use Bepado\SDK\Gateway;
use Bepado\SDK\Struct;
use Bepado\SDK\ShopFactory;
use Bepado\SDK\ChangeVisitor;
use Bepado\SDK\Logger;

/**
 * Shopping service
 *
 * @version $Revision$
 */
class Shopping
{
    /**
     * Shop registry
     *
     * @var ShopFactory
     */
    protected $shopFactory;

    /**
     * Change visitor
     *
     * Visits arrays of changes into corresponding messages
     *
     * @var ChangeVisitor
     */
    protected $changeVisitor;

    public function __construct(
        ShopFactory $shopFactory,
        ChangeVisitor $changeVisitor,
        Logger $logger
    ) {
        $this->shopFactory = $shopFactory;
        $this->changeVisitor = $changeVisitor;
        $this->logger = $logger;
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
     * @return void
     */
    public function checkProducts(Struct\Order $order)
    {
        $responses = array();
        $orders = $this->splitShopOrders($order);
        foreach ($orders as $shopId => $order) {
            $shopGateway = $this->shopFactory->getShopGateway($shopId);
            $responses[$shopId] = $shopGateway->checkProducts($order);
        }

        $result = array();
        foreach ($responses as $shopId => $changes) {
            if ($changes !== true) {
                $result = array_merge(
                    $result,
                    $this->changeVisitor->visit($changes)
                );
            }
        }

        return $result ?: true;
    }

    /**
     * Reserve products
     *
     * This method will reserve the given products in the remote shops.
     *
     * If the product data change in a relevant way, this method will not
     * reserve the products, but instead return a Struct\Message, which should
     * be ACK'ed by the user. Afterwards another reservation may be issued.
     *
     * If The reservation of the product set succeeded a hash of reservation
     * Ids for all involved shops will be returned. This hash must be stored in
     * the shop for all further transactions. The session is probably the best
     * location for this.
     *
     * If data updated are detected, the local product database will be updated
     * accordingly.
     *
     * @TODO: How do we want to handle the case that some shop reserve the
     * order as requested, and others complain. Just ignore because it is bound
     * to happen really seldom?
     *
     * @param Struct\Order $order
     * @return Struct\Reservation
     */
    public function reserveProducts(Struct\Order $order)
    {
        $responses = array();
        $orders = $this->splitShopOrders($order);
        foreach ($orders as $shopId => $order) {
            $shopGateway = $this->shopFactory->getShopGateway($shopId);
            $responses[$shopId] = $shopGateway->reserveProducts($order);
        }

        $reservation = new Struct\Reservation();
        $reservation->orders = $orders;
        foreach ($responses as $shopId => $response) {
            if (is_string($response)) {
                $reservation->orders[$shopId]->reservationId = $response;
            } elseif (is_array($response)) {
                $reservation->messages[$shopId] = $this->changeVisitor->visit($response);
            } else {
                // TODO: How to react on false value returned?
                // This might occur if a reservation is canceled by the provider shop
                // see Service\Transaction::reserveProducts().
                // SDK::reserveProducts() needs an according update, too.
                return false;
            }
        }

        return $reservation;
    }

    /**
     * Checkout product sets related to the given reservation Ids
     *
     * This process is the final "buy" transaction. It should be part of the
     * checkout process and be handled synchronously.
     *
     * This method will just return true, if the transaction worked as
     * expected. If it failed, or partially failed, a corresponding
     * Struct\Message will be returned.
     *
     * @param string[] $products
     * @return mixed
     */
    public function checkout(Struct\Reservation $reservation)
    {
        $results = array();
        foreach ($reservation->orders as $shopId => $order) {
            $shopGateway = $this->shopFactory->getShopGateway($shopId);

            $results[$shopId] =
                $shopGateway->buy($order->reservationId) &&
                $shopGateway->confirm($order->reservationId);

            if ($results[$shopId] === true) {
                $this->logger->log($order);
            }
        }

        return $results;
    }

    /**
     * Split shop orders
     *
     * Returns an array of orders per shop
     *
     * @param Struct\Order $order
     * @return Struct\Order[]
     */
    protected function splitShopOrders(Struct\Order $order)
    {
        $orders = array();
        foreach ($this->getShopIds($order) as $shopId) {
            $shopOrder = clone $order;
            $shopOrder->providerShop = $shopId;
            $shopOrder->products = $this->getShopProducts($order, $shopId);
            $orders[$shopId] = $shopOrder;
        }

        return $orders;
    }

    protected function getShopIds(Struct\Order $order)
    {
        return array_unique(
            array_map(
                function (Struct\OrderItem $orderItem) {
                    return $orderItem->product->shopId;
                },
                $order->products
            )
        );
    }

    protected function getShopProducts(Struct\Order $order, $shopId)
    {
        return array_filter(
            $order->products,
            function (Struct\OrderItem $orderItem) use ($shopId) {
                return $orderItem->product->shopId === $shopId;
            }
        );
    }
}
