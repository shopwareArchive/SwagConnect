<?php
/**
 * This file is part of the Shopware Connect SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Shopware\Connect\ShippingCostCalculator;

use Shopware\Connect\Gateway\ShippingCosts;
use Shopware\Connect\ShippingCostCalculator;
use Shopware\Connect\ShippingCosts\Rules;
use Shopware\Connect\Struct;
use Shopware\Connect\Struct\Order;
use Shopware\Connect\Struct\OrderItem;
use Shopware\Connect\Struct\Shipping;

/**
 * Calculate shipping costs based on rules from the gateway.
 */
class RuleCalculator implements ShippingCostCalculator
{
    /**
     * VAT calculator
     *
     * @var RuleCalculator\VatCalculator
     */
    protected $vatCalculator;

    public function __construct(RuleCalculator\VatCalculator $vatCalculator = null)
    {
        $this->vatCalculator = $vatCalculator ?: new RuleCalculator\VatCalculator();
    }

    /**
     * Get shipping costs for order
     *
     * @param \Shopware\Connect\ShippingCosts\Rules $shippingCostRules
     * @param \Shopware\Connect\Struct\Order $order
     *
     * @return \Shopware\Connect\Struct\Shipping
     */
    public function calculateShippingCosts(Rules $shippingCostRules, Order $order)
    {
        $shippingCostRules->vatConfig->vat = $this->vatCalculator->calculateVat($order, $shippingCostRules->vatConfig);

        $minShippingCosts = null;
        $minShippingCostValue = PHP_INT_MAX;
        foreach ($shippingCostRules as $shippingCostRule) {
            if ($shippingCostRule->isApplicable($order)) {
                $shippingCosts = $shippingCostRule->getShippingCosts($order, $shippingCostRules->vatConfig);
                if ($shippingCosts->shippingCosts < $minShippingCostValue) {
                    $minShippingCosts = $shippingCosts;
                }
            }
        }

        if (!$minShippingCosts) {
            return new Shipping(
                array(
                    'isShippable' => false,
                )
            );
        }

        return $minShippingCosts;
    }
}
