<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * @version 1.0.0snapshot201303151129
 */

namespace Bepado\SDK\Service;

use Bepado\SDK\ProductFromShop;
use Bepado\SDK\Gateway;
use Bepado\SDK\Logger;
use Bepado\SDK\Struct;

/**
 * Service to maintain transactions
 *
 * @version 1.0.0snapshot201303151129
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
        Logger $logger
    ) {
        $this->fromShop = $fromShop;
        $this->reservations = $reservations;
        $this->logger = $logger;
    }

    /**
     * Check order in shop
     *
     * Verifies, if all orders in the given order still have the same price
     * and availability.
     *
     * Returns true on success, or an array of Struct\Change with updates for
     * the requested orders.
     *
     * @param Struct\Order $order
     * @return mixed
     */
    public function checkProducts(Struct\Order $order)
    {
        $currentProducts = $this->fromShop->getProducts(
            array_map(
                function ($orderItem) {
                    return $orderItem->product->sourceId;
                },
                $order->products
            )
        );

        $changes = array();
        foreach ($order->products as $orderItem) {
            $product = $orderItem->product;
            foreach ($currentProducts as $current) {
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
        $verify = $this->checkProducts($order);
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
     * @return mixed
     */
    public function buy($reservationId)
    {
        try {
            $order = $this->reservations->getOrder($reservationId);
            $order->localOrderId = $this->fromShop->buy($order);
            $order->reservationId = $reservationId;
            $this->reservations->setBought($reservationId, $order);
        } catch (\Exception $e) {
            return false;
        }
        return true;
    }

    /**
     * Confirm a reservation in the remote shop.
     *
     * Returns true on success, or a Struct\Message on failure. SHOULD never
     * fail.
     *
     * @param string $reservationId
     * @return mixed
     */
    public function confirm($reservationId)
    {
        try {
            $order = $this->reservations->getOrder($reservationId);
            $this->reservations->setConfirmed($reservationId);
            $this->logger->log($order);
        } catch (\Exception $e) {
            return false;
        }
        return true;
    }
}
