<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * @version 1.0.129
 */

namespace Bepado\SDK\Struct;

use Bepado\SDK\Struct;

/**
 * Struct class with additional internal properties for shop items
 *
 * All properties in this class are internal to the SDK. Users should not care.
 * Properties will be overwritten by the SDK anyways.
 *
 * @version 1.0.129
 */
abstract class ShopItem extends Struct
{
    /**
     * ID of the shop.
     *
     * Will be set by the SDK.
     *
     * @var string
     * @access private
     */
    public $shopId;

    /**
     * Product revision
     *
     * Will be set by the SDK.
     *
     * @var string
     * @access private
     */
    public $revisionId;
}
