<?php
/**
 * This file is part of the Shopware Connect SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Shopware\Connect\Gateway;

use Shopware\Connect\Struct;

/**
 * Gateway interface to maintain product hashes and exported products
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 * @api
 */
interface ReservationGateway
{
    /**
     * Create and store reservation
     *
     * Returns the reservation Id. You may want to store the reservation ID in
     * the order before storing the order. You can also just return the primary
     * ID of your reservation table or something alike.
     *
     * You should be able to fetch the order back later by the reservation ID.
     * It is just meant for you to be able to identify and retrieve an order.
     *
     * @param Struct\Order $order
     * @return string
     */
    public function createReservation(Struct\Order $order);

    /**
     * Get order for reservation Id
     *
     * @param string $reservationId
     * @return Struct\Order
     */
    public function getOrder($reservationId);

    /**
     * Set reservation as bought
     *
     * @param string $reservationId
     * @param Struct\Order $order
     * @return void
     */
    public function setBought($reservationId, Struct\Order $order);

    /**
     * Set reservation as confirmed
     *
     * @param string $reservationId
     * @return void
     */
    public function setConfirmed($reservationId);
}
