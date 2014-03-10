<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * @version 1.1.142
 */

namespace Bepado\SDK\Struct;

use Bepado\SDK\Struct;

/**
 * Change struct
 *
 * @version 1.1.142
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
