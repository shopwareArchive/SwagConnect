<?php

namespace Bepado\SDK\Struct;

use Bepado\SDK\Struct;

class ShippingCosts extends Struct
{
    /**
     * @var integer
     */
    public $shopId;

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
