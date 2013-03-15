<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * @version 1.0.0snapshot201303151129
 */

namespace Bepado\SDK\Struct\Change\ToShop;

use Bepado\SDK\Struct\Change;

/**
 * Delete change struct
 *
 * @version 1.0.0snapshot201303151129
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
