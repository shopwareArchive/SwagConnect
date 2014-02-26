<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * @version 1.0.129
 */

namespace Bepado\SDK;

/**
 * Shipping cost calculator
 *
 * @version 1.0.129
 */
class ShippingCostCalculator
{
    /**
     * Shipping cost calculator
     *
     * @var ShippingCostCalculator
     */
    protected $calculator;

    public function __construct(
        Gateway\ShopConfiguration $configuration
    ) {
        $this->configuration = $configuration;
    }

    /**
     * Get shipping costs for order
     *
     * @param Struct\Order $order
     * @return Struct\ShippingCosts
     */
    public function calculateOrderShippingCosts(Struct\Order $order)
    {
        $products = array();
        foreach ($order->products as $orderItem) {
            $products[] = $orderItem->product;
        }

        return $this->getShippingCosts($products);
    }

    /**
     * Get shipping costs for order
     *
     * @param Struct\Order $order
     * @return Struct\ShippingCosts
     */
    public function calculateProductListShippingCosts(Struct\ProductList $productList)
    {
        return $this->getShippingCosts($productList->products);
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

        $shopId = key($shopIds);

        if (!$productCount) {
            return 0.;
        }

        $shopConfiguration = $this->configuration->getShopConfiguration($product->shopId);
        $netShippingCost = $shopConfiguration->shippingCost;

        return new Struct\ShippingCosts(
            array(
                'shopId' => $shopId,
                'shippingCosts' => $netShippingCost,
                'grossShippingCosts' => $netShippingCost * (1 + $maxVat),
            )
        );
    }
}
