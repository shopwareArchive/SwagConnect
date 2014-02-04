<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * @version $Revision$
 */

namespace Bepado\SDK;

/**
 * Abstract base class to store SDK related data
 *
 * You may create custom extensions of this class, if the default data stores
 * do not work for you.
 *
 * @version $Revision$
 * @api
 */
abstract class Gateway implements
    Gateway\ChangeGateway,
    Gateway\ProductGateway,
    Gateway\RevisionGateway,
    Gateway\ShopConfiguration,
    Gateway\ReservationGateway
{
}
