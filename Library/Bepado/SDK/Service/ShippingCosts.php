<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Bepado\SDK\Service;

use Bepado\SDK\Gateway;
use Bepado\SDK\ShippingCostCalculator;
use Bepado\SDK\ShippingCosts\Rules;
use Bepado\SDK\Struct\Order;

/**
 * Service to maintain transactions
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */
class ShippingCosts
{
    /**
     * Shipping costs gateway
     *
     * @var Gateway\ShippingCosts
     */
    protected $shippingCosts;

    /**
     * Shipping cost calculator
     *
     * @var ShippingCostCalculator
     */
    protected $calculator;

    /**
     * COnstruct from gateway
     *
     * @param Gateway\ShippingCosts $shippingCosts
     * @param ShippingCostCalculator $calculator
     * @return void
     */
    public function __construct(
        Gateway\ShippingCosts $shippingCosts,
        ShippingCostCalculator $calculator
    ) {
        $this->shippingCosts = $shippingCosts;
        $this->calculator = $calculator;
    }

    /**
     * Get last revision
     *
     * @return string
     */
    public function lastRevision()
    {
        return $this->shippingCosts->getLastShippingCostsRevision();
    }

    /**
     * Replicate changes
     *
     * @param array $changes
     * @return void
     */
    public function replicate(array $changes)
    {
        foreach ($changes as $change) {
            $this->shippingCosts->storeShippingCosts(
                $change['from_shop_id'],
                $change['to_shop_id'],
                $change['revision'],
                $change['shippingCosts'],
                isset($change['customerCosts']) ? $change['customerCosts'] : $change['shippingCosts']
            );
        }
    }

    /**
     * Get shipping costs for order
     *
     * @param \Bepado\SDK\Struct\Order $order
     * @param string $type
     *
     * @return \Bepado\SDK\Struct\Order
     */
    public function calculateShippingCosts(Order $order, $type)
    {
        $rules = $this->getShippingCostRules($order, $type);

        return $this->calculator->calculateShippingCosts($rules, $order);
    }

    /**
     * Get shipping cost rules for current order
     *
     * @param \Bepado\SDK\Struct\Order $order
     * @return Rule[]
     */
    protected function getShippingCostRules(Order $order, $type)
    {
        if (empty($order->providerShop) || empty($order->orderShop)) {
            throw new \InvalidArgumentException(
                "Order#providerShop and Order#orderShop must be non-empty ".
                "to calculate the shipping costs."
            );
        }

        foreach ($order->products as $orderItem) {
            if ($orderItem->product->shopId != $order->providerShop) {
                throw new \InvalidArgumentException(
                    "ShippingCostCalculator can only calculate shipping costs for " .
                    "products belonging to exactly one remote shop."
                );
            }
        }

        $rules = $this->shippingCosts->getShippingCosts($order->providerShop, $order->orderShop, $type);
        if (is_array($rules)) {
            // This is for legacy shops, where the rules are still just an array
            $rules = new Rules(array('rules' => $rules));
        }

        if ( ! $rules->vatConfig) {
            $rules->vatConfig = new \Bepado\SDK\ShippingCosts\VatConfig(array(
                'mode' => isset($rules->vatMode) ? $rules->vatMode : \Bepado\SDK\ShippingCosts\Rules::VAT_MAX,
                'vat' => $rules->vat,
            ));
        }

        return $rules;
    }
}
