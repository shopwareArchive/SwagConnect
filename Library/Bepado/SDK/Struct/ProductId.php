<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Bepado\SDK\Struct;

use Bepado\SDK\Struct;

/**
 * Represents identifier of a Bepado product
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
