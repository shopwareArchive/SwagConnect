<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Bepado\SDK\Service;

use Bepado\SDK\ProductFromShop;
use Bepado\SDK\Gateway;
use Bepado\SDK\Logger;
use Bepado\SDK\Struct;
use Bepado\SDK\Struct\VerificatorDispatcher;
use Bepado\SDK\ShippingCostCalculator;

/**
 * Service to maintain transactions
 *
 * This service is the only one that will be called remotely from other SDK
 * instances.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */
class Transaction
{
    /**
     * Implementation of the interface to receive orders from the shop
     *
     * @var ProductFromShop
     */
    protected $fromShop;

    /**
     * Reservation gateway
     *
     * @var Gateway\ReservationGateway
     */
    protected $reservations;

    /**
     * Logger
     *
     * @var Logger
     */
    protected $logger;

    /**
     * ShopConfiguration
     *
     * @var ShopConfiguration
     */
    protected $shopConfiguration;

    /**
     * Shipping cost service
     *
     * @var ShippingCosts
     */
    protected $shippingCostsService;

    /**
     * @var \Bepado\SDK\Struct\VerificatorDispatcher
     */
    protected $verificator;

    /**
     * @var string
     */
    protected $apiKey;

    /**
     * COnstruct from gateway
     *
     * @param ProductFromShop $fromShop
     * @param Gateway\ReservationGateway $reservations
     * @param Logger $logger
     * @return void
     */
    public function __construct(
        ProductFromShop $fromShop,
        Gateway\ReservationGateway $reservations,
        Logger $logger,
        Gateway\ShopConfiguration $shopConfiguration,
        ShippingCosts $shippingCostsService,
        VerificatorDispatcher $verificator,
        $apiKey
    ) {
        $this->fromShop = $fromShop;
        $this->reservations = $reservations;
        $this->logger = $logger;
        $this->shopConfiguration = $shopConfiguration;
        $this->verificator = $verificator;
        $this->apiKey = $apiKey;
    }

    /**
     * Check products that will be part of an order in shop
     *
     * Verifies, if all products in the list still have the same price
     * and availability as on the remote shop.
     *
     * Returns true on success, or an array of Struct\Change with updates for
     * the requested orders.
     *
     * @param Struct\Order $order
     * @return Struct\CheckResult
     */
    public function checkProducts(Struct\Order $order, $buyerShopId)
    {
        $this->verificator->verify($order);

        if (count($order->products) === 0) {
            throw new \InvalidArgumentException(
                "ProductList is not allowed to be empty in remote Transaction#checkProducts()"
            );
        }


        $localProducts = $this->getLocalProducts($order);

        $myShopId = $this->shopConfiguration->getShopId();

        $checkResult = new Struct\CheckResult();
        foreach ($order->products as $orderItem) {
            $remoteProduct = $orderItem->product;
            if (!isset($localProducts[$remoteProduct->sourceId])) {
                // Product does not exist any more
                $checkResult->changes[] = new Struct\Change\InterShop\Delete(
                    array(
                        'sourceId' => $remoteProduct->sourceId,
                    )
                );

                continue;
            }

            $localProduct = $localProducts[$remoteProduct->sourceId];
            $localProduct->shopId = $myShopId;

            if ($this->purchasePriceHashInvalid($remoteProduct)) {

                $currentNotAvailable = clone $localProduct;
                $currentNotAvailable->availability = 0;

                $checkResult->changes[] = new Struct\Change\InterShop\Unavailable(
                    array(
                        'sourceId' => $remoteProduct->sourceId,
                        'shopId' => $myShopId,
                    )
                );

            } elseif ($this->fixedPriceChanged($localProduct, $remoteProduct) || $this->productUnavailable($localProduct)) {

                // Price or availability changed
                $checkResult->changes[] = new Struct\Change\InterShop\Unavailable(
                    array(
                        'sourceId' => $remoteProduct->sourceId,
                        'shopId' => $myShopId,
                    )
                );
            }
        }

        if (count($checkResult->changes) === 0) {
            $checkResult->aggregatedShippingCosts = $this->fromShop->calculateShippingCosts($order);
            $checkResult->shippingCosts[] = $checkResult->aggregatedShippingCosts;
        }

        return $checkResult;
    }

    /**
     * Get current products from the database indexed by source-id.
     *
     * @return array<string, \Bepado\SDK\Struct\Product>
     */
    private function getLocalProducts(Struct\Order $order)
    {
        $localProducts = $this->fromShop->getProducts(
            array_map(
                function ($orderItem) {
                    return $orderItem->product->sourceId;
                },
                $order->products
            )
        );

        $indexedProducts = array();

        foreach ($localProducts as $product) {
            $indexedProducts[$product->sourceId] = $product;
        }

        return $indexedProducts;
    }

    private function purchasePriceHashInvalid($remoteProduct)
    {
        $acceptableHash = PurchasePriceSecurity::hash(
            $remoteProduct->purchasePrice,
            $remoteProduct->offerValidUntil,
            $this->apiKey
        );

        // Hash is invalid
        if ($acceptableHash !== $remoteProduct->purchasePriceHash) {
            return true;
        }

        // Offer Is Not Valid Anymore, bepado platform needs to update hashes and validity regularly.
        if ($remoteProduct->offerValidUntil < time()) {
            return true;
        }

        return false;
    }

    private function fixedPriceChanged($localProduct, $remoteProduct)
    {
        return ($localProduct->fixedPrice && ! $this->floatsEqual($localProduct->price, $remoteProduct->price));
    }

    private function productUnavailable($localProduct)
    {
        return $localProduct->availability <= 0;
    }

    private function floatsEqual($a, $b)
    {
        return abs($a - $b) < 0.000001;
    }

    /**
     * Reserve order in shop
     *
     * ProductGateway SHOULD be reserved and not be sold out while bing reserved.
     * Reservation may be cancelled after sufficient time has passed.
     *
     * Returns a reservationId on success, or an array of Struct\Change with
     * updates for the requested orders.
     *
     * @param Struct\Order $order
     * @return mixed
     */
    public function reserveProducts(Struct\Order $order)
    {
        $this->verificator->verify($order);

        $productCheckResult = $this->checkProducts($order, $order->orderShop);
        if (count($productCheckResult->changes)) {
            return $productCheckResult;
        }
        $myShippingCosts = $productCheckResult->aggregatedShippingCosts;

        if (!$myShippingCosts->isShippable) {
            return new Struct\Message(array(
                'message' => 'Products cannot be shipped to %country.',
                'values' => array(
                    'country' => $order->deliveryAddress->country
                )
            ));
        }

        try {
            $reservationId = $this->reservations->createReservation($order);
            $this->fromShop->reserve($order);
        } catch (\Exception $e) {
            return new Struct\Error(
                array(
                    'message' => $e->getMessage(),
                    'debugText' => (string) $e,
                )
            );
        }
        return $reservationId;
    }

    /**
     * Buy order associated with reservation in the remote shop.
     *
     * Returns true on success, or a Struct\Message on failure. SHOULD never
     * fail.
     *
     * @param string $reservationId
     * @param string $orderId
     * @return mixed
     */
    public function buy($reservationId, $orderId)
    {
        try {
            $order = $this->reservations->getOrder($reservationId);

            $productCheckResult = $this->checkProducts($order, $order->orderShop);
            $myShippingCosts = $productCheckResult->aggregatedShippingCosts;

            $orderShop = $order->orderShop;
            $providerShop = $order->providerShop;

            $order->localOrderId = $orderId;
            $order->providerOrderId = $this->fromShop->buy($order);
            $order->reservationId = $reservationId;

            $order->orderShop = $orderShop;
            $order->providerShop = $providerShop;

            $this->reservations->setBought($reservationId, $order);
            return $this->logger->log($order);
        } catch (\Exception $e) {
            return new Struct\Error(
                array(
                    'message' => $e->getMessage(),
                    'debugText' => (string) $e,
                )
            );
        }
    }

    /**
     * Confirm a reservation in the remote shop.
     *
     * Returns true on success, or a Struct\Message on failure. SHOULD never
     * fail.
     *
     * @param string $reservationId
     * @param string $remoteLogTransactionId
     * @return mixed
     */
    public function confirm($reservationId, $remoteLogTransactionId)
    {
        try {
            $order = $this->reservations->getOrder($reservationId);
            $this->reservations->setConfirmed($reservationId);
            $this->logger->confirm($remoteLogTransactionId);
        } catch (\Exception $e) {
            return new Struct\Error(
                array(
                    'message' => $e->getMessage(),
                    'debugText' => (string) $e,
                )
            );
        }
        return true;
    }
}
