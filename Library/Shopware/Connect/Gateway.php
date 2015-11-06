<?php
/**
 * This file is part of the Shopware Connect SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Shopware\Connect;

/**
 * Abstract base class to store SDK related data
 *
 * You may create custom extensions of this class, if the default data stores
 * do not work for you.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
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
