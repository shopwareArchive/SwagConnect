<?php
/**
 * This file is part of the Shopware Connect SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Shopware\Connect\Service;

use Shopware\Connect\ProductFromShop;
use Shopware\Connect\Gateway;
use Shopware\Connect\Logger;
use Shopware\Connect\Struct;
use Shopware\Connect\Struct\VerificatorDispatcher;
use Shopware\Connect\ShippingCostCalculator;
use Shopware\Connect\Exception;

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
     * @var \Shopware\Connect\Struct\VerificatorDispatcher
     */
    protected $verificator;

    /**
     * @var string
     */
    protected $apiKey;

    /**
     * @var \Shopware\Connect\Service\SocialNetwork
     */
    protected $socialNetwork;

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
        VerificatorDispatcher $verificator,
        SocialNetwork $socialNetwork,
        $apiKey
    ) {
        $this->fromShop = $fromShop;
        $this->reservations = $reservations;
        $this->logger = $logger;
        $this->shopConfiguration = $shopConfiguration;
        $this->verificator = $verificator;
        $this->socialNetwork = $socialNetwork;
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
        $toShopConfiguration = $this->shopConfiguration->getShopConfiguration($buyerShopId);

        $checkResult = new Struct\CheckResult();
        /** @var Struct\OrderItem $orderItem */
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

                $checkResult->changes[] = new Struct\Change\InterShop\Update(
                    array(
                        'sourceId' => $remoteProduct->sourceId,
                        'shopId' => $myShopId,
                        'product' => $currentNotAvailable,
                        'oldProduct' => $remoteProduct,
                    )
                );

            } elseif ($this->fixedPriceChanged($localProduct, $remoteProduct)) {
                // Price changed
                $checkResult->changes[] = new Struct\Change\InterShop\Update(
                    array(
                        'sourceId' => $remoteProduct->sourceId,
                        'shopId' => $myShopId,
                        'product' => $localProduct,
                        'oldProduct' => $remoteProduct,
                    )
                );
            } elseif ($this->productUnavailable($localProduct, $orderItem->count)) {
                // Availability changed
                $checkResult->changes[] = new Struct\Change\InterShop\Unavailable(
                    array(
                        'sourceId' => $remoteProduct->sourceId,
                        'shopId' => $myShopId,
                        'availability' => $localProduct->availability,
                    )
                );
            }
        }

        if (count($checkResult->changes) === 0) {
            $checkResult->aggregatedShippingCosts = $this->calculateConsumerShippingCosts($toShopConfiguration, $order);
            $checkResult->shippingCosts[] = $checkResult->aggregatedShippingCosts;
        }

        return $checkResult;
    }

    private function calculateConsumerShippingCosts(Struct\ShopConfiguration $toShopConfiguration, Struct\Order $order)
    {
        switch ($toShopConfiguration->shippingCostType) {
            case "remote":
                // From shop calculates shipping cost and both merchant and consumer have to pay that.
                return $this->fromShop->calculateShippingCosts($order);

            case "all":
            case "filtered":

                return new Struct\Shipping(array(
                    'isShippable' => $this->connectShippingDestinationAllowed($order->deliveryAddress),
                    'shippingCosts' => 0,
                    'grossShippingCosts' => 0,
                    'deliveryWorkDays' => $this->maxDeliveryWorkDays($order)
                ));

            default:
                throw new Exception\InvalidArgumentException(sprintf("Shipping Cost Type '%s' does not exist.", $toShopConfiguration->shippingCostType));
        }
    }

    /**
     * Is shipping to this destination allowed when using Shopware Connect Shipping rules.
     *
     * @return bool
     */
    private function connectShippingDestinationAllowed(Struct\Address $address)
    {
        if ($address->country !== 'DEU') {
            return false;
        }

        // 18565 (Hiddensee), 25849 (Pellworm), 25859 (Hooge), 25863
        // (Langeneß), 25869 (Gröde), 25938 (Föhr), 25946 (Amrum), 25980,
        // 25992, 25996, 25997, 25999 (Sylt) 26465 (Langeoog), 26474
        // (Spiekeroog), 26486 (Wangerooge), 26548 (Norderney), 26571 (Juist),
        // 26579 (Baltrum), 26757 (Borkum), 27498 (Helgoland)
        $germanIslands = array(
            18565,
            25849,
            25859,
            25863,
            25869,
            25938,
            25946,
            25980,
            25992,
            25996,
            25997,
            25999,
            26465,
            26474,
            26486,
            26548,
            26571,
            26579,
            26757,
            27498
        );

        if (in_array($address->zip, $germanIslands)) {
            return false;
        }

        return true;
    }

    private function calculateMerchantShippingCosts(Struct\ShopConfiguration $toShopConfiguration, Struct\Order $order)
    {
        switch ($toShopConfiguration->shippingCostType) {
            case "remote":
                // only the merchant pays the shops direct shipping costs
                return $this->fromShop->calculateShippingCosts($order);

            case "filtered":
            case "all":
                return $this->socialNetwork->calculateShippingCosts($order);

            default:
                throw new Exception\InvalidArgumentException(sprintf("Shipping Cost Type '%s' does not exist.", $toShopConfiguration->shippingCostType));
        }
    }

    /**
     * @return int
     */
    private function maxDeliveryWorkDays($order)
    {
        return array_reduce($order->orderItems, function ($maxDays, $item) {
            return max($maxDays, $item->product->deliveryWorkDays);
        }, 0);
    }

    /**
     * Get current products from the database indexed by source-id.
     *
     * @return array<string, \Shopware\Connect\Struct\Product>
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

    private function productUnavailable($localProduct, $requestedCount)
    {
        return ($localProduct->availability <= 0 || $localProduct->availability < $requestedCount) && !$this->shopConfiguration->isFeatureEnabled('sellNotInStock');
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
            $toShopConfiguration = $this->shopConfiguration->getShopConfiguration($order->orderShop);
            $order->shipping = $this->calculateMerchantShippingCosts($toShopConfiguration, $order);

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
