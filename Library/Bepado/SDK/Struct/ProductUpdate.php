<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Bepado\SDK\Struct;

use Bepado\SDK\Struct;

/**
 * Update product price/availability details.
 *
 * This change is used for optimization purposes, allowing to change the
 * important metadata of a product very efficiently with a few SQL update
 * statements.
 */
class ProductUpdate extends Struct
{
    /**
     * @var float
     */
    public $price;

    /**
     * @var float
     */
    public $purchasePrice;

    /**
     * @var string
     */
    public $purchasePriceHash;

    /**
     * @var int
     */
    public $offerValidUntil;

    /**
     * @var int
     */
    public $availability;
}
