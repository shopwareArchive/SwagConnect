<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * @version 1.1.142
 */

namespace Bepado\SDK\ShippingCostCalculator;

use Bepado\SDK\Gateway\ShopConfiguration;
use Bepado\SDK\ShippingCostCalculator;
use Bepado\SDK\Struct\Order;
use Bepado\SDK\Struct\OrderItem;
use Bepado\SDK\Struct\ShippingCosts;

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
     * @param \Bepado\SDK\Struct\Order $order
     * @return \Bepado\SDK\Struct\TotalShippingCosts
     */
    public function calculateShippingCosts(Order $order)
    {
        return $this->getShippingCosts(
            array_map(function (OrderItem $item) {
                return $item->product;
            },
            $order->products)
        );
    }

    /**
     * Get shipping costs
     *
     * @param Struct\Product[] $products
     * @return Struct\ShippingCosts
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
            return new ShippingCosts(
                array(
                    'isShippable' => false,
                )
            );
        }

        $shopConfiguration = $this->configuration->getShopConfiguration($product->shopId);
        $netShippingCost = $shopConfiguration->shippingCost;

        return new ShippingCosts(
            array(
                'shippingCosts' => $netShippingCost,
                'grossShippingCosts' => $netShippingCost * (1 + $maxVat),
                'isShippable' => true,
            )
        );
    }
}
