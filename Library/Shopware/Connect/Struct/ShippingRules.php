<?php
/**
 * This file is part of the Shopware Connect SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Shopware\Connect\Struct;

use Shopware\Connect\Struct;
use Shopware\Connect\ShippingCosts\Rule;

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
