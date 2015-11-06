<?php
/**
 * This file is part of the Shopware Connect SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Shopware\Connect\Struct;

/**
 * The shipping cost total for all the remote bepado orders.
 */
class TotalShippingCosts extends Shipping
{
    /**
     * Key value pairs of shop ids and shipping costs.
     *
     * @var array<int,Shipping>
     */
    public $shops = array();
}
