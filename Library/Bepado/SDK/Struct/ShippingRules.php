<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Bepado\SDK\Struct;

use Bepado\SDK\Struct;
use Bepado\SDK\ShippingCosts\Rule;

/**
 * Definition of Product Shipping rules.
 */
class ShippingRules extends Struct
{
    /**
     * Array of shipping rules
     *
     * @var Rule\Product[]
     */
    public $rules = array();
}
