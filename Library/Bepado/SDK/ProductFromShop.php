<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * @version 1.0.0snapshot201303151129
 */

namespace Bepado\SDK;

/**
 * Interface for product providers
 *
 * @version 1.0.0snapshot201303151129
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
}
