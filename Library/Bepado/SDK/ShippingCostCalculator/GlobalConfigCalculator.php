<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Bepado\SDK\ShippingCostCalculator;

use Bepado\SDK\Gateway\ShopConfiguration;
use Bepado\SDK\ShippingCostCalculator;
use Bepado\SDK\ShippingCosts\Rules;
use Bepado\SDK\Struct\Order;
use Bepado\SDK\Struct\OrderItem;
use Bepado\SDK\Struct\Shipping;

class GlobalConfigCalculator implements ShippingCostCalculator
{
    /**
     * Shipping cost calculator
     *
     * @var ShippingCostCalculator
     */
    protected $calculator;

    public function __construct(ShopConfiguration $configuration)
    {
        $this->configuration = $configuration;
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
        // temporary workaround for not having my own global shipping costs
        if ($order->shippingCosts !== null && $order->grossShippingCosts !== null) {
            return new Shipping(
                array(
                    'shippingCosts' => $order->shippingCosts,
                    'grossShippingCosts' => $order->grossShippingCosts,
                    'isShippable' => true,
                )
            );
        }

        return $this->getShippingCosts(
            array_map(
                function (OrderItem $item) {
                    return $item->product;
                },
                $order->products
            )
        );
    }

    /**
     * Get shipping costs
     *
     * @param Struct\Product[] $products
     * @return Struct\Shipping
     */
    protected function getShippingCosts(array $products)
    {
        $productCount = 0;
        $shopIds = array();
        $maxVat = 0;
        foreach ($products as $product) {
            $shopIds[$product->shopId] = true;
            $maxVat = max($maxVat, $product->vat);

            if (!$product->freeDelivery) {
                ++$productCount;
            }
        }

        if (count($shopIds) > 1) {
            throw new \InvalidArgumentException(
                "ShippingCostCalculator can only calculate shipping costs for " .
                "products belonging to exactly one remote shop."
            );
        }

        if (!$productCount) {
            return new Shipping(
                array(
                    'isShippable' => false,
                )
            );
        }

        $shopConfiguration = $this->configuration->getShopConfiguration($product->shopId);
        $netShippingCost = $shopConfiguration->shippingCost;

        return new Shipping(
            array(
                'shippingCosts' => $netShippingCost,
                'grossShippingCosts' => $netShippingCost * (1 + $maxVat),
                'isShippable' => true,
            )
        );
    }
}
