<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * @version 1.1.133
 */

namespace Bepado\SDK\Struct;

use Bepado\SDK\Struct;

class ShippingCosts extends Struct
{
    /**
     * @var integer
     */
    public $shopId;

    /**
     * @return bool
     */
    public $isShippable = true;

    /**
     * Net shipping costs.
     *
     * @var float
     */
    public $shippingCosts;

    /**
     * Gross shipping costs with VAT applied.
     *
     * @var float
     */
    public $grossShippingCosts;
}
