<?php
/**
 * This file is part of the Shopware Connect SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Shopware\Connect\Struct\Change\FromShop;

use Shopware\Connect\Struct\Change;

/**
 * Availability change struct
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 * @api
 */
class Availability extends Change
{
    /**
     * New availability for product
     *
     * @var int
     */
    public $availability;
} 
