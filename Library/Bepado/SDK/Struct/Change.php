<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Bepado\SDK\Struct;

use Bepado\SDK\Struct;

/**
 * Change struct
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 * @api
 */
abstract class Change extends Struct
{
    /**
     * Product ID in source shop
     *
     * @var string
     */
    public $sourceId;

    /**
     * Revision of change
     *
     * @var float
     */
    public $revision;

    /**
     * ID of the shop the affected product is from
     *
     * @var string
     */
    public $shopId;
}
