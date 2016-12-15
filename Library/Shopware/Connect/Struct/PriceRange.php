<?php
/**
 * This file is part of the Shopware Connect SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Shopware\Connect\Struct;

use Shopware\Connect\Struct;

/**
 * Struct class, representing price range information for a product
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 * @api
 */
class PriceRange extends Struct
{
    CONST ANY = 'any';

    /**
     * If it is string then the value must be 'any'
     *
     * @var int|string
     */
    public $to;

    /**
     * @var int
     */
    public $from;

    /**
     * @var string
     */
    public $customerGroupKey;

    /**
     * @var float
     */
    public $price;
}