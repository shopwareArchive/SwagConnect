<?php
/**
 * This file is part of the Shopware Connect SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Shopware\Connect\ShippingCosts;

use Shopware\Connect\Struct;

class VatConfig extends Struct
{
    /**
     * How to calculate the VAT for the shipping costs.
     *
     * @var string
     */
    public $mode = Rules::VAT_MAX;

    /**
     * @var float
     */
    public $vat = 0;

    /**
     * Flag if shipping costs are provided as net (or gross)
     *
     * @var bool
     */
    public $isNet = true;
}
