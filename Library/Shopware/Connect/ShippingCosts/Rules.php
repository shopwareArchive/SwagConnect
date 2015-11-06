<?php
/**
 * This file is part of the Shopware Connect SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Shopware\Connect\ShippingCosts;

use Shopware\Connect\Struct;
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
     * @var \Shopware\Connect\Struct\ShippingCost\Rule[]
     */
    public $rules = array();

    /**
     * Default delivery work days
     *
     * @var int
     */
    public $defaultDeliveryWorkDays = 10;

    /**
     * VAT config
     *
     * @var VatConfig
     */
    public $vatConfig;

    public function __construct(array $values = array())
    {
        $this->vatConfig = new VatConfig();
        parent::__construct($values);
    }

    public function getIterator()
    {
        return new ArrayIterator($this->rules);
    }

    /**
     * Getter for legacy conformity
     *
     * @param string $property
     * @return mixed
     */
    public function __get($property)
    {
        switch ($property) {
            case 'vat':
                return $this->vatConfig->vat;
            case 'vatMode':
                return $this->vatConfig->mode;
            default:
                return parent::__get($property);
        }
    }

    /**
     * Setter for legacy conformity
     *
     * @param string $property
     * @param mixed $value
     * @return void
     */
    public function __set($property, $value)
    {
        switch ($property) {
            case 'vat':
                return $this->vatConfig->vat = $value;
            case 'vatMode':
                return $this->vatConfig->mode = $value;
            default:
                return parent::__set($property, $value);
        }
    }
}
