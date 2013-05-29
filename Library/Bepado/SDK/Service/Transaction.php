<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * @version $Revision$
 */

namespace Bepado\SDK\Service;

use Bepado\SDK\ProductFromShop;
use Bepado\SDK\Gateway;
use Bepado\SDK\Logger;
use Bepado\SDK\Struct;

/**
 * Service to maintain transactions
 *
 * @version $Revision$
 */
class Transaction
{
    /**
     * Implementation of the interface to receive orders from the shop
     *
     * @var ProductFromShop
     */
    protected $fromShop;

    /**
     * Reservation gateway
     *
     * @var Gateway\ReservationGateway
     */
    protected $reservations;

    /**
     * Logger
     *
     * @var Logger
     */
    protected $logger;

    /**
     * ShopConfiguration
     *
     * @var ShopConfiguration
     */
    protected $shopConfiguration;

    /**
     * COnstruct from gateway
     *
     * @param ProductFromShop $fromShop
     * @param Gateway\ReservationGateway $reservations
     * @param Logger $logger
     * @return void
     */
    public function __construct(
        ProductFromShop $fromShop,
        Gateway\ReservationGateway $reservations,
        Logger $logger,
        Gateway\ShopConfiguration $shopConfiguration
    ) {
        $this->fromShop = $fromShop;
        $this->reservations = $reservations;
        $this->logger = $logger;
        $this->shopConfiguration = $shopConfiguration;
    }

    /**
     * Check products that will be part of an order in shop
     *
     * Verifies, if all products in the list still have the same price
     * and availability as on the remote shop.
     *
     * Returns true on success, or an array of Struct\Change with updates for
     * the requested orders.
     *
     * @param Struct\ProductList $products
     * @return mixed
     */
    public function checkProducts(Struct\ProductList $products)
    {
        if (count($products->products) === 0) {
            throw new \InvalidArgumentException(
                "ProductList is not allowed to be empty in remote Transaction#checkProducts()"
            );
        }

        $currentProducts = $this->fromShop->getProducts(
            array_map(
                function ($product) {
                    return $product->sourceId;
                },
                $products->products
            )
        );

        $myShopId = $this->shopConfiguration->getShopId();

        $changes = array();
        foreach ($products->products as $product) {
            foreach ($currentProducts as $current) {
                $current->shopId = $myShopId;

                if ($current->sourceId === $product->sourceId) {
                    if (($current->price !== $product->price) ||
                        ($current->availability < $product->availability)) {

                        // Price or availability changed
                        $changes[] = new Struct\Change\InterShop\Update(
                            array(
                                'sourceId' => $product->sourceId,
                                'product' => $current,
                                'oldProduct' => $product,
                            )
                        );
                    }
                }

                continue 2;
            }

            // Product does not exist any more
            $changes[] = new Struct\Change\InterShop\Delete(
                array(
                    'sourceId' => $product->sourceId,
                )
            );
        }

        return $changes ?: true;
    }

    /**
     * Reserve order in shop
     *
     * ProductGateway SHOULD be reserved and not be sold out while bing reserved.
     * Reservation may be cancelled after sufficient time has passed.
     *
     * Returns a reservationId on success, or an array of Struct\Change with
     * updates for the requested orders.
     *
     * @param Struct\Order $order
     * @return mixed
     */
    public function reserveProducts(Struct\Order $order)
    {
        $products = array();
        foreach ($order->products as $orderItem) {
            $products[] = $orderItem->product;
        }

        $verify = $this->checkProducts(
            new Struct\ProductList(
                array(
                    'products' => $products
                )
            )
        );

        if ($verify !== true) {
            return $verify;
        }

        try {
            $reservationId = $this->reservations->createReservation($order);
            $this->fromShop->reserve($order);
        } catch (\Exception $e) {
            return false;
        }
        return $reservationId;
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
        try {
            $order = $this->reservations->getOrder($reservationId);
            $order->localOrderId = $orderId;
            $order->providerOrderId = $this->fromShop->buy($order);
            $order->reservationId = $reservationId;
            $this->reservations->setBought($reservationId, $order);
            return $this->logger->log($order);
        } catch (\Exception $e) {
            return false;
        }
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
        try {
            $order = $this->reservations->getOrder($reservationId);
            $this->reservations->setConfirmed($reservationId);
            $this->logger->confirm($remoteLogTransactionId);
        } catch (\Exception $e) {
            return false;
        }
        return true;
    }
}
