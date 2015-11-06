<?php
/**
 * This file is part of the Shopware Connect SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Shopware\Connect\Struct\Change\ToShop;

use Shopware\Connect\Struct\Change;

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
