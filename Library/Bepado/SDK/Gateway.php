<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * @version 1.1.133
 */

namespace Bepado\SDK;

/**
 * Abstract base class to store SDK related data
 *
 * You may create custom extensions of this class, if the default data stores
 * do not work for you.
 *
 * @version 1.1.133
 * @api
 */
abstract class Gateway implements
    Gateway\ChangeGateway,
    Gateway\ProductGateway,
    Gateway\RevisionGateway,
    Gateway\ShopConfiguration,
    Gateway\ReservationGateway,
    Gateway\ShippingCosts
{
}
