<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Bepado\SDK\Gateway;

use Bepado\SDK\Struct;
use Bepado\SDK\ShippingCosts\Rules;

/**
 * Gateaway interface to maintain shipping costs
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 * @api
 */
interface ShippingCosts
{
    const SHIPPING_COSTS_INTERSHOP = 'intershop';
    const SHIPPING_COSTS_CUSTOMER = 'customer';

    /**
     * Get last revision
     *
     * @return string
     */
    public function getLastShippingCostsRevision();

    /**
     * Store shop shipping costs
     *
     * @param string $fromShop
     * @param string $toShop
     * @param string $revision
     * @param \Bepado\SDK\ShippingCosts\Rules $intershopCosts
     * @param \Bepado\SDK\ShippingCosts\Rules $customerCosts
     * @return void
     */
    public function storeShippingCosts($fromShop, $toShop, $revision, Rules $intershopCosts, Rules $customerCosts);

    /**
     * Get shop shipping costs
     *
     * @param string $fromShop
     * @param string $toShop
     * @param string $type
     * @return \Bepado\SDK\ShippingCosts\Rules
     */
    public function getShippingCosts($fromShop, $toShop, $type = self::SHIPPING_COSTS_INTERSHOP);
}
