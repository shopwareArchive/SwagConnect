<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Bepado\SDK\Struct\Change\FromShop;

use Bepado\SDK\Struct\Change;

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