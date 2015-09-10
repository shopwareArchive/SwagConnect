<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Bepado\SDK\Struct;

use Bepado\SDK\Struct;

class Configuration extends Struct
{
    /**
     * @var array<\Bepado\SDK\Struct\ShopConfiguration>
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
     * @var \Bepado\SDK\Struct\Address
     */
    public $billingAddress;
}
