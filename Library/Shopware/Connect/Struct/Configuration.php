<?php
/**
 * This file is part of the Shopware Connect SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Shopware\Connect\Struct;

use Shopware\Connect\Struct;

class Configuration extends Struct
{
    /**
     * @var array<\Shopware\Connect\Struct\ShopConfiguration>
     */
    public $shops = array();

    /**
     * @var array
     */
    public $features = array();

    /**
     * @var int
     */
    public $priceType;

    /**
     * @var \Shopware\Connect\Struct\Address
     */
    public $billingAddress;
}
