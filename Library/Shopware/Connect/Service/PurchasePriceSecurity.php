<?php
/**
 * This file is part of the Shopware Connect SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Shopware\Connect\Service;

/**
 * Handles security code related to purchase price verification.
 */
class PurchasePriceSecurity
{
    /**
     * @param float $purchasePrice
     * @param int $offerValidUntil
     * @param string $fromApiKey
     * @return string
     */
    public static function hash($purchasePrice, $offerValidUntil, $fromApiKey)
    {
        return hash_hmac('sha256', sprintf('%.3F %d', $purchasePrice, $offerValidUntil), $fromApiKey);
    }
}
