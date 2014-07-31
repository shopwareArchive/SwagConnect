<?php
/**
 * This file is part of the Bepado Common Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Bepado\SDK\ShippingCosts\Rule;

use Bepado\SDK\ShippingCosts\Rule;
use Bepado\SDK\Struct\Order;
use Bepado\SDK\Struct\Shipping;
use Bepado\SDK\ShippingCosts\VatConfig;

/**
 * Decorator for orders from specific countries.
 *
 * Only applys nested rule when from the given countries.
 */
class CountryDecorator extends Rule
{
    /**
     * ISO-3 Country codes
     *
     * @var array<string>
     */
    public $countries = array();

    /**
     * Exclude addresses with given zip codes.
     *
     * Matches are evaluated from the beginning and case insensitive.
     *
     * @var array<string>
     */
    public $excludeZipCodes = array();

    /**
     * @var \Bepado\SDK\ShippingCosts\Rule
     */
    public $delegatee;

    /**
     * Check if shipping cost is applicable to given order
     *
     * @param Order $order
     * @return bool
     */
    public function isApplicable(Order $order)
    {
        return
            $this->matchesCountry($order->deliveryAddress->country) &&
            !$this->matchesExcludedZipCode($order->deliveryAddress->zip) &&
            $this->delegatee->isApplicable($order)
        ;
    }

    private function matchesCountry($country)
    {
        return in_array(
            $country,
            $this->countries
        );
    }

    private function matchesExcludedZipCode($zipCode)
    {
        return strlen($zipCode) && count(
            array_filter(
                $this->excludeZipCodes,
                function ($excludeZipCode) use ($zipCode) {
                    return stripos($zipCode, $excludeZipCode) === 0;
                }
            )
        ) > 0;
    }

    /**
     * Get shipping costs for order
     *
     * Returns the net shipping costs.
     *
     * @param Order $order
     * @param VatConfig $vatConfig
     * @return Shipping
     */
    public function getShippingCosts(Order $order, VatConfig $vatConfig)
    {
        return $this->delegatee->getShippingCosts($order, $vatConfig);
    }
}
