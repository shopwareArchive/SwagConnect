<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Bepado\SDK\ShippingCostCalculator\RuleCalculator;

use Bepado\SDK\ShippingCosts\Rules;
use Bepado\SDK\ShippingCosts\VatConfig;
use Bepado\SDK\Struct\Order;
use Bepado\SDK\Struct\OrderItem;

class VatCalculator
{
    /**
     * Get maximum VAT of all products
     *
     * This seems to be a safe assumption to apply the maximum VAT of all
     * products to the shipping costs.
     *
     * @note We could refactor this into a dispatcher with different
     * VatCalculators for the different VAT modes. This would reduce the method
     * complexity drastically.
     *
     * @param Order $order
     * @param VatConfig $vatConfig
     * @return float
     */
    public function calculateVat(Order $order, VatConfig $vatConfig)
    {
        switch ($vatConfig->mode) {
            case Rules::VAT_MAX:
                return max(
                    array_map(
                        function (OrderItem $orderItem) {
                            return $orderItem->product->vat;
                        },
                        $order->orderItems
                    )
                );

            case Rules::VAT_DOMINATING:
                $prices = array();

                foreach ($order->orderItems as $orderItem) {
                    if (!isset($prices[(string)$orderItem->product->vat])) {
                        $prices[(string)$orderItem->product->vat] = 0;
                    }

                    $prices[(string)$orderItem->product->vat] += $orderItem->product->price * $orderItem->count;
                }

                arsort($prices);
                reset($prices);

                return key($prices);

            case Rules::VAT_PROPORTIONATELY:
                $totalPrice = 0;
                $vat = 0;

                if (count($order->orderItems) === 1) {
                    return $order->orderItems[0]->product->vat;
                }

                foreach ($order->orderItems as $orderItem) {
                    $totalPrice += $orderItem->product->purchasePrice * $orderItem->count;
                }

                foreach ($order->orderItems as $orderItem) {
                    $productPrice = $orderItem->product->purchasePrice * $orderItem->count;
                    $vat += ($productPrice / $totalPrice) * $orderItem->product->vat;
                }

                return $vat;

            case Rules::VAT_FIX:
                return $vatConfig->vat;

            default:
                throw new \RuntimeException("Unknown VAT mode specified: " . $vatConfig->mode);
        }
    }
}
