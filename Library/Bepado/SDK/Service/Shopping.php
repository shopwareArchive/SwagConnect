<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Bepado\SDK\Service;

use Bepado\SDK\Gateway;
use Bepado\SDK\Struct;
use Bepado\SDK\ShopFactory;
use Bepado\SDK\ShopGateway;
use Bepado\SDK\ChangeVisitor;
use Bepado\SDK\ProductToShop;
use Bepado\SDK\Logger;
use Bepado\SDK\ErrorHandler;
use Bepado\SDK\ShippingCostCalculator;
use Bepado\SDK\Gateway\ShopConfiguration;
use Bepado\SDK\Struct\CheckResult;
use Bepado\SDK\ShippingCostCalculator\Aggregator;

/**
 * Shopping service
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */
class Shopping
{
    /**
     * Shop registry
     *
     * @var ShopFactory
     */
    protected $shopFactory;

    /**
     * Change visitor
     *
     * Visits arrays of changes into corresponding messages
     *
     * @var ChangeVisitor
     */
    protected $changeVisitor;

    /**
     * Product to shop gateway
     *
     * Stores changes to products reported by the remote shop
     *
     * @var ProductToShop
     */
    protected $productToShop;

    /**
     * Logger
     *
     * @var Logger
     */
    protected $logger;

    /**
     * Error Handler
     *
     * @var ErrorHandler
     */
    protected $errorHandler;

    /**
     * Shipping cost service
     *
     * @var ShippingCosts
     */
    protected $shippingCostsService;

    /**
     * Shipping cost aggregator
     *
     * @var Aggregator
     */
    protected $aggregator;

    /**
     * @var ShopConfiguration
     */
    protected $config;

    public function __construct(
        ShopFactory $shopFactory,
        ChangeVisitor $changeVisitor,
        ProductToShop $productToShop,
        Logger $logger,
        ErrorHandler $errorHandler,
        ShippingCosts $shippingCostsService,
        ShopConfiguration $config,
        Aggregator $aggregator = null
    ) {
        $this->shopFactory = $shopFactory;
        $this->changeVisitor = $changeVisitor;
        $this->productToShop = $productToShop;
        $this->logger = $logger;
        $this->errorHandler = $errorHandler;
        $this->shippingCostsService = $shippingCostsService;
        $this->config = $config;
        $this->aggregator = $aggregator ?: new Aggregator\Sum();
    }

    /**
     * Calculate shipping costs
     *
     * Calculate shipping costs for the given set of products.
     *
     * @param Struct\Order $order
     * @param string $type
     *
     * @return Struct\Order
     */
    public function calculateShippingCosts(Struct\Order $order, $type)
    {
        $shops = array();
        $order->orderShop = $this->config->getShopId();
        $orders = $this->splitShopOrders($order);

        foreach ($orders as $shopId => $shopOrder) {
            $shops[$shopId] = $this->shippingCostsService->calculateShippingCosts($shopOrder, $type);
            $shops[$shopId]->shopId = $shopId;
        }

        $aggregator = new ShippingCostCalculator\Aggregator\Sum();
        $shipping = $aggregator->aggregateShippingCosts($shops, new Struct\TotalShippingCosts());
        $shipping->shops = $shops;

        return $shipping;
    }

    /**
     * Check products still are in the state they are stored locally
     *
     * This method will verify with the remote shops that products are still in
     * the expected state. If the state of products changed this method will
     * return a Struct\Message, which should be ACK'ed by the user. Otherwise
     * this method will just return true.
     *
     * If data updated are detected, the local product database will be updated 
     * accordingly.
     *
     * This method is a convenience method to check the state of a set of
     * remote products. The state will be checked again during
     * reserveProducts().
     *
     * @param Struct\Order $productList
     * @return Struct\CheckResult
     */
    public function checkProducts(Struct\Order $order)
    {
        $orders = $this->splitShopOrders($order);
        return $this->checkSplitOrders($orders);
    }

    /**
     * @param Struct\Order[<string>] $orders
     * @return Struct\CheckResult
     */
    private function checkSplitOrders(array $orders)
    {
        $myShopId = $this->config->getShopId();

        $checkResults = array();

        foreach ($orders as $shopId => $order) {
            $shopGateway = $this->shopFactory->getShopGateway($shopId);
            $shopCheckResult = $shopGateway->checkProducts($order, $myShopId);

            if (count($shopCheckResult->changes)) {
                $this->applyRemoteShopChanges($shopCheckResult->changes);
            }

            $checkResults[] = $shopCheckResult;
        }

        return $this->aggregateCheckResults($checkResults);
    }

    /**
     * @param Struct\CheckResult[] $shopCheckResults
     * @return Struct\CheckResult
     */
    private function aggregateCheckResults(array $shopCheckResults)
    {
        $checkResult = new CheckResult();

        foreach ($shopCheckResults as $shopCheckResult) {
            $checkResult->changes = array_merge($checkResult->changes, $shopCheckResult->changes);
            if ($shopCheckResult->shippingCosts !== null) {
                $checkResult->shippingCosts = array_merge(
                    $checkResult->shippingCosts,
                    $shopCheckResult->shippingCosts
                );
            }
        }

        $checkResult->errors = $this->changeVisitor->visit($checkResult->changes);
        $checkResult->aggregatedShippingCosts = $this->aggregator->aggregateShippingCosts(
            $checkResult->shippingCosts
        );

        return $checkResult;
    }

    /**
     * Reserve products
     *
     * This method will reserve the given products in the remote shops.
     *
     * If the product data change in a relevant way, this method will not
     * reserve the products, but instead the messages property will contain
     * messages, which should be displayed to the user and the success property
     * will be false. The messages should be ACK'ed by the user. Afterwards
     * another reservation may be issued.
     *
     * If The reservation of the product set succeeded the orders in the
     * reservation struct will have a reservationID set. The reservation struct
     * should be sored in the shop for the checkout process. The session is
     * probably the best location for this.
     *
     * If data updated are detected, the local product database will be updated
     * accordingly.
     *
     * During reservation, the remote shops return the shipping costs resulting
     * from the order. These will be included in the returned Struct\Reservation.
     *
     * @TODO: How do we want to handle the case that some shop reserve the
     * order as requested, and others complain. Just ignore because it is bound
     * to happen really seldom?
     *
     * @param Struct\Order $order
     * @return Struct\Reservation
     */
    public function reserveProducts(Struct\Order $order)
    {
        $responses = array();
        $orders = $this->splitShopOrders($order);

        $checkResult = $this->checkSplitOrders($orders);
        $aggregatedShippingCosts = $checkResult->aggregatedShippingCosts;
        $splitShippingCost = $checkResult->shippingCosts;

        if (!$aggregatedShippingCosts->isShippable) {
            return $this->failedReservationNotShippable($orders, $aggregatedShippingCosts->shippingCosts);
        }

        /** @var \Bepado\SDK\Struct\Shipping $shipping */
        foreach($checkResult->shippingCosts as $shipping) {
            if (isset($orders[$shipping->shopId])) {
                $orders[$shipping->shopId]->shipping = $shipping;
            }
        }

        foreach ($orders as $shopId => $order) {
            $shopGateway = $this->shopFactory->getShopGateway($shopId);
            $responses[$shopId] = $shopGateway->reserveProducts($this->anonymizeCustomerEmail($order));
        }

        $reservation = new Struct\Reservation();
        $reservation->orders = $orders;
        $reservation->aggregatedShippingCosts = $aggregatedShippingCosts;

        foreach ($responses as $shopId => $response) {
            if (is_string($response)) {
                $reservation->orders[$shopId]->reservationId = $response;
            } elseif ($response instanceof Struct\CheckResult) {
                $this->applyRemoteShopChanges($response->changes);
                $reservation->messages[$shopId] = $this->changeVisitor->visit($response->changes);

                if (count($reservation->messages[$shopId]) === 0) {
                    $reservation->messages[$shopId] = array(
                        new Struct\Message(array(
                            'message' => 'An error occured on the remote shop during reservation, order is cancelled.'
                        ))
                    );
                }
            } elseif ($response instanceof Struct\Message) {
                $reservation->messages[$shopId] = array($response);
            } else {
                // TODO: How to react on false value returned?
                // This might occur if a reservation is canceled by the provider shop
                // see Service\Transaction::reserveProducts().
                // SDK::reserveProducts() needs an according update, too.
                return false;
            }
        }

        $reservation->success = !count($reservation->messages);
        return $reservation;
    }

    /**
     * Anonymize a customer email.
     *
     * @return \Bepado\SDK\Struct\Order
     */
    private function anonymizeCustomerEmail(Struct\Order $order)
    {
        if ($order->deliveryAddress->email
            && strpos($order->deliveryAddress->email, "@mail.bepado.com") === false) {

            $remoteOrder = clone $order;
            $remoteOrder->deliveryAddress->email = sprintf(
                'marketplace-%s-%s@mail.bepado.com',
                $order->orderShop,
                md5($remoteOrder->deliveryAddress->email)
            );

            return $remoteOrder;
        }

        return $order;
    }

    /**
     * Create failed reservation with order not shippable error messages.
     *
     * @return Struct\Reservation
     */
    private function failedReservationNotShippable(array $orders, Struct\TotalShippingCosts $shippingCosts)
    {
        $reservation = new Struct\Reservation();
        $reservation->orders = $orders;
        $reservation->success = false;
        $reservation->messages = array();

        foreach ($shippingCosts->shops as $shopId => $shopShippingCosts) {
            if (!$shopShippingCosts->isShippable) {
                $reservation->messages[$shopId] = array(
                    new Struct\Message(array(
                        'message' => 'Products cannot be shipped to %country.',
                        'values' => array(
                            'country' => $orders[$shopId]->deliveryAddress->country
                        )
                    ))
                );
            }
        }

        return $reservation;
    }

    /**
     * Apply changes reported by a remote shop
     *
     * @param Struct\Change[] $changes
     * @return void
     */
    protected function applyRemoteShopChanges(array $changes)
    {
        $this->productToShop->startTransaction();
        foreach ($changes as $change) {
            switch (true) {
                case ($change instanceof Struct\Change\InterShop\Update):
                case ($change instanceof Struct\Change\InterShop\Unavailable):
                    $this->productToShop->changeAvailability($change->shopId, $change->sourceId, 0);
                    break;

                case ($change instanceof Struct\Change\InterShop\Delete):
                    $this->productToShop->delete($change->shopId, $change->sourceId);
                    break;

                default:
                    throw new \RuntimeException(
                        'Invalid change class provided: ' . get_class($change)
                    );
            }
        }
        $this->productToShop->commit();
    }

    /**
     * Checkout product sets related to the given reservation Ids
     *
     * This process is the final "buy" transaction. It should be part of the
     * checkout process and be handled synchronously.
     *
     * This method will just return true, if the transaction worked as
     * expected. If it failed, or partially failed, a corresponding
     * Struct\Message will be returned.
     *
     * @param Struct\Reservation $reservation
     * @param string $orderId
     * @return mixed
     */
    public function checkout(Struct\Reservation $reservation, $orderId)
    {
        $results = array();
        foreach ($reservation->orders as $shopId => $order) {
            $order->localOrderId = $orderId;
            $shopGateway = $this->shopFactory->getShopGateway($shopId);

            if (($transactionIds = $this->tryBuy($shopGateway, $order, $orderId)) === false) {
                $results[$shopId] = false;
                continue;
            }

            if ($this->tryConfirm($shopGateway, $order, $transactionIds) === false) {
                $results[$shopId] = false;
                continue;
            }

            $results[$shopId] = true;
        }

        return $results;
    }

    /**
     * Try to issue a buy in the remote shop
     *
     * Returns false if an error occured. Returns the remote log transaction ID
     * otherwise.
     *
     * @param ShopGateway $shopGateway
     * @param Struct\Order $order
     * @param string $orderId
     * @return mixed
     */
    protected function tryBuy(ShopGateway $shopGateway, Struct\Order $order, $orderId)
    {
        $response = $shopGateway->buy($order->reservationId, $orderId);
        if ($response instanceof Struct\Error) {
            $this->errorHandler->handleError($response);
            return false;
        }

        if (!$response) {
            throw new \RuntimeException("Unexpected response: " . var_export($response, true));
        }

        try {
            $transactionId = $this->logger->log($order);
        } catch (\Exception $e) {
            $this->errorHandler->handleException($e);
            return false;
        }

        return array(
            'local' => $transactionId,
            'remote' => $response,
        );
    }

    /**
     * Try to issue a confirm in the remote shop
     *
     * Returns false if an error occured. Returns true, if the checkout
     * succeeeded.
     *
     * @param ShopGateway $shopGateway
     * @param Struct\Order $order
     * @param array $transactionIds
     * @return bool
     */
    protected function tryConfirm(ShopGateway $shopGateway, Struct\Order $order, array $transactionIds)
    {
        $response = $shopGateway->confirm($order->reservationId, $transactionIds['remote']);
        if ($response instanceof Struct\Error) {
            $this->errorHandler->handleError($response);
            return false;
        }

        if (!$response) {
            throw new \RuntimeException("Unexpected response: " . var_export($response, true));
        }

        try {
            $this->logger->confirm($transactionIds['local']);
        } catch (\Exception $e) {
            $this->errorHandler->handleException($e);
            return false;
        }

        return true;
    }

    /**
     * Split shop orders
     *
     * Returns an array of orders per shop
     *
     * @param Struct\Order $order
     * @return Struct\Order[]
     */
    protected function splitShopOrders(Struct\Order $order)
    {
        $orders = array();
        foreach ($this->getShopIds($order) as $shopId) {
            $shopOrder = clone $order;
            $shopOrder->providerShop = $shopId;
            $shopOrder->products = $this->getShopProducts($order, $shopId);

            $orders[$shopId] = $shopOrder;
        }

        return $orders;
    }

    /**
     * Get Shop IDs
     *
     * @param Struct\Order $order
     * @return string[]
     */
    protected function getShopIds(Struct\Order $order)
    {
        return array_unique(
            array_map(
                function (Struct\OrderItem $orderItem) {
                    return $orderItem->product->shopId;
                },
                $order->products
            )
        );
    }

    /**
     * Get order items of a single shop
     *
     * @param Struct\Order $order
     * @param string $shopId
     * @return Struct\OrderItem[]
     */
    protected function getShopProducts(Struct\Order $order, $shopId)
    {
        return array_filter(
            $order->products,
            function (Struct\OrderItem $orderItem) use ($shopId) {
                return $orderItem->product->shopId === $shopId;
            }
        );
    }
}
