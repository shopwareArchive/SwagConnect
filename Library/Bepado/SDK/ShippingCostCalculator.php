<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * @version $Revision$
 */

namespace Bepado\SDK;

/**
 * Shipping cost calculator
 *
 * @version $Revision$
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
     * @return float
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
     * @return float
     */
    public function calculateProductListShippingCosts(Struct\ProductList $productList)
    {
        return $this->getShippingCosts($productList->products);
    }

    /**
     * Get shipping costs
     *
     * @param Struct\Product[] $products
     * @return float
     */
    protected function getShippingCosts(array $products)
    {
        $productCount = 0;
        foreach ($products as $product) {
            if (!$product->freeDelivery) {
                ++$productCount;
            }
        }

        if (!$productCount) {
            return 0.;
        }

        $shopConfiguration = $this->configuration->getShopConfiguration($product->shopId);
        return $shopConfiguration->shippingCost;
    }
}
