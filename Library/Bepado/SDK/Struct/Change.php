<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * @version $Revision$
 */

namespace Bepado\SDK\Struct;

use Bepado\SDK\Struct;

/**
 * Change struct
 *
 * @version $Revision$
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

    /**
     * SDK endpoint URL of the shop the affected product is from
     *
     * @var string
     */
    public $shopEndpoint;

    /**
     * From-Shop Display Name
     *
     * @var string
     */
    public $shopDisplayName;

    /**
     * From-Shop Url
     *
     * @var string
     */
    public $shopUrl;

    /**
     * Communication Token that From-Shop can reproduce.
     *
     * Token is used for order communication between the two shops to verify
     * that their communication is valid.
     *
     * @var string
     */
    public $shopToken;
}
