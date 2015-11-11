<?php
/**
 * This file is part of the Shopware Connect SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Shopware\Connect\Struct;

use Shopware\Connect\Struct;

/**
 * Represents identifier of a Shopware Connect product
 */
class ProductId extends Struct
{
    /**
     * @var integer
     */
    public $shopId;

    /**
     * @var string
     */
    public $sourceId;
}
