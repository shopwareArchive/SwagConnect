<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Bepado\SDK\ShippingCostCalculator;

use Bepado\SDK\ShippingCostCalculator;
use Bepado\SDK\ShippingRuleParser;
use Bepado\SDK\Struct\Order;
use Bepado\SDK\Struct\OrderItem;
use Bepado\SDK\Struct\ShippingRule;
use Bepado\SDK\Struct\Shipping;
use Bepado\SDK\ShippingCosts\Rule;
use Bepado\SDK\ShippingCosts\Rules;

/**
 * Calculate shipping costs based on product rules
 */
class ProductCalculator implements ShippingCostCalculator
{
    /**
     * Aggregate for products without specific rules
     *
     * @var ShippingCostCalculator
     */
    private $aggregate;

    /**
     * Shipping rule parser
     *
     * @var ShippingRuleParser
     */
    private $parser;

    /**
     * Shipping cost aggregator
     *
     * @var Aggregator
     */
    private $aggregator;

    public function __construct(
        ShippingCostCalculator $aggregate,
        ShippingRuleParser $parser,
        Aggregator $aggregator = null
    ) {
        $this->aggregate = $aggregate;
        $this->parser = $parser;
        $this->aggregator = $aggregator ?: new Aggregator\Sum();
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
        $productOrder = clone $order;
        $commonOrder = clone $order;

        $productOrder->orderItems = array_filter(
            $productOrder->orderItems,
            function (OrderItem $orderItem) {
                return (bool) $orderItem->product->shipping;
            }
        );

        $commonOrder->orderItems = array_filter(
            $commonOrder->orderItems,
            function (OrderItem $orderItem) {
                return !$orderItem->product->shipping;
            }
        );

        foreach ($productOrder->orderItems as $orderItem) {
            $rules = $this->parser->parseString($orderItem->product->shipping);

            $orderItem->shipping = new Shipping(array('isShippable' => false));
            foreach ($rules->rules as $rule) {
                $rule->deliveryWorkDays = $rule->deliveryWorkDays ?: $shippingCostRules->defaultDeliveryWorkDays;
                $rule->orderItemCount = $orderItem->count;
                $rule->vat = $orderItem->product->vat;

                if ($rule->isApplicable($productOrder)) {
                    $orderItem->shipping = $rule->getShippingCosts($productOrder, $shippingCostRules->vatConfig);
                    break;
                }
            }
        }

        $shippingCosts = array_map(
            function (OrderItem $orderItem) {
                return $orderItem->shipping;
            },
            $productOrder->orderItems
        );
        if ($commonOrder->orderItems) {
            $shippingCosts[] = $this->aggregate->calculateShippingCosts($shippingCostRules, $commonOrder);
        }

        return $this->aggregator->aggregateShippingCosts($shippingCosts);
    }
}
