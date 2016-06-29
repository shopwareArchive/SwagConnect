<?php
/**
 * This file is part of the Shopware Connect SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Shopware\Connect;

/**
 * Interface for product providers
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 * @api
 */
interface ProductFromShop
{
    /**
     * Get product data
     *
     * Get product data for all the product IDs specified in the given string
     * array.
     *
     * @param string[] $ids
     * @return Struct\Product[]
     */
    public function getProducts(array $ids);

    /**
     * Get all IDs of all exported products
     *
     * @return string[]
     */
    public function getExportedProductIDs();

    /**
     * Calculate shipping costs for $order
     *
     * @param Struct\Order $order
     * @return Struct\Shipping
     */
    public function calculateShippingCosts(Struct\Order $order);

    /**
     * Reserve a product in shop for purchase
     *
     * @param Struct\Order $order
     * @return void
     * @throws \Exception Abort reservation by throwing an exception here.
     */
    public function reserve(Struct\Order $order);

    /**
     * Buy products mentioned in order
     *
     * Should return the internal order ID.
     *
     * @param Struct\Order $order
     * @return string
     *
     * @throws \Exception Abort buy by throwing an exception,
     *                    but only in very important cases.
     *                    Do validation in {@see reserve} instead.
     */
    public function buy(Struct\Order $order);

    /**
     * Update payment status of an order processed through bepado.
     *
     * An order can be identified with PaymentStatus#localOrderId
     * and the status be updated in your order locally, when
     * the payment is made in bepado.
     *
     * @param Struct\PaymentStatus $status
     * @return void
     */
    public function updatePaymentStatus(Struct\PaymentStatus $status);

    /**
     * Perform sync changes to fromShop
     * FromShop can store revision for each exported product.
     *
     * @param string $since
     * @param \Shopware\Connect\Struct\Change[] $changes
     * @return void
     */
    public function onPerformSync($since, array $changes);
}
