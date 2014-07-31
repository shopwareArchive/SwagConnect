<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Bepado\SDK\ShippingCostCalculator;

use Bepado\SDK\Gateway\ShippingCosts;
use Bepado\SDK\ShippingCostCalculator;
use Bepado\SDK\ShippingCosts\Rules;
use Bepado\SDK\Struct;
use Bepado\SDK\Struct\Order;
use Bepado\SDK\Struct\OrderItem;
use Bepado\SDK\Struct\Shipping;

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
     * @param \Bepado\SDK\ShippingCosts\Rules $shippingCostRules
     * @param \Bepado\SDK\Struct\Order $order
     *
     * @return \Bepado\SDK\Struct\Shipping
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
