<?php
/**
 * This file is part of the Shopware Connect SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Shopware\Connect\Struct;

use Shopware\Connect\Struct;

class CheckResult extends Struct
{
    /**
     * @var Struct\Change[]
     */
    public $changes = array();

    /**
     * Errors
     *
     * @var Message[]
     */
    public $errors = array();

    /**
     * @var Shipping[]
     */
    public $shippingCosts = array();

    /**
     * @var Shipping
     */
    public $aggregatedShippingCosts;

    /**
     * Has errors
     *
     * @return bool
     */
    public function hasErrors()
    {
        return (bool) count($this->errors);
    }
}
