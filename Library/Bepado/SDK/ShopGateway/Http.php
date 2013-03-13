<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * @version 1.0.0snapshot201303061109
 */

namespace Bepado\SDK\ShopGateway;

use Bepado\SDK\ShopGateway;

/**
 * Shop gateway HTTP implementation
 *
 * Gateway to interact with other shops
 *
 * @version 1.0.0snapshot201303061109
 */
class Http extends ShopGateway
{
    public function __construct()
    {

    }

    /**
     * Reserve order in remote shop
     *
     * Products SHOULD be reserved and not be sold out while bing reserved.
     * Reservation may be cancelled after sufficient time has passed.
     *
     * Returns true on success, or an array of Struct\Change with updates for
     * the requested products.
     *
     * @param Struct\Order
     * @return mixed
     */
    public function checkProducts(Struct\Order $order)
    {
        throw new \RuntimeException("@TODO: Implement");
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
    public function reserve(Struct\Order $order)
    {
        throw new \RuntimeException("@TODO: Implement");
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
        throw new \RuntimeException("@TODO: Implement");
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
        throw new \RuntimeException("@TODO: Implement");
    }
}
