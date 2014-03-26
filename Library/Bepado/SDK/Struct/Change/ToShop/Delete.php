<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Bepado\SDK\Struct\Change\ToShop;

use Bepado\SDK\Struct\Change;

/**
 * Delete change struct
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 * @api
 */
class Delete extends Change
{
    /**
     * Shop id
     *
     * @var string
     */
    public $shopId;
}
