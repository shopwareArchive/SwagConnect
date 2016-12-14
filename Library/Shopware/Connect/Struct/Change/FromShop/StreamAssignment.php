<?php
/**
 * This file is part of the Shopware Connect SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Shopware\Connect\Struct\Change\FromShop;

use Shopware\Connect\Struct\Change;

/**
 * StreamAssignment change struct
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 * @api
 */
class StreamAssignment extends Change
{
    /**
     * Assigned streams in supplier shop
     *
     * @var array
     */
    public $supplierStreams = array();
}
