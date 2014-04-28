<?php
/**
 * This file is part of the Bepado Common Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Bepado\SDK\ShippingCosts;

use Bepado\SDK\Struct;
use IteratorAggregate;
use ArrayIterator;

/**
 * A list of rules and additional information about calculation.
 */
class Rules extends Struct implements IteratorAggregate
{
    /**
     * Calculate VAT as the max VAT of all the products in the basket.
     */
    const VAT_MAX = 'max';

    /**
     * Calculate VAT as the weighted average of all the products in the basket.
     */
    const VAT_DOMINATING = 'dominating';

    /**
     * Calculate VAT with a fixed, predefined value
     */
    const VAT_FIX = 'fix';

    /**
     * Calculate VAT proprotionately based on the basket.
     */
    const VAT_PROPORTIONATELY = 'proportionately';

    /**
     * How to calculate the VAT for the shipping costs.
     *
     * @var string
     */
    public $vatMode = self::VAT_MAX;

    /**
     * @var float
     */
    public $vat = 0;

    /**
     * @var \Bepado\SDK\Struct\ShippingCost\Rule[]
     */
    public $rules = array();

    public function getIterator()
    {
        return new ArrayIterator($this->rules);
    }

    /**
     * Restores Rules from a previously stored state array.
     *
     * @param array $state
     * @return \Bepado\SDK\ShippingCosts\Rules
     */
    public static function __set_state(array $state)
    {
        return new Rules($state);
    }
}
