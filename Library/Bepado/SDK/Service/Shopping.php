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
     * Shipping cost calculator
     *
     * @var ShippingCostCalculator
     */
    protected $calculator;

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
        ShippingCostCalculator $calculator,
        ShopConfiguration $config
    ) {
        $this->shopFactory = $shopFactory;
        $this->changeVisitor = $changeVisitor;
        $this->productToShop = $productToShop;
        $this->logger = $logger;
        $this->errorHandler = $errorHandler;
        $this->calculator = $calculator;
        $this->config = $config;
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
            $shops[$shopId] = $this->calculator->calculateShippingCosts($shopOrder, $type);
            $shops[$shopId]->shopId = $shopId;
        }

        $isShippable = array_reduce(
            $shops,
            function ($all, $shippingCosts) {
                return $all && $shippingCosts->isShippable;
            },
            true
        );

        $netShippingCosts = array_sum(
            array_map(function (Struct\ShippingCosts $costs) {
                    return $costs->shippingCosts;
                },
                $shops
            )
        );

        $grossShippingCosts = array_sum(
            array_map(function (Struct\ShippingCosts $costs) {
                    return $costs->grossShippingCosts;
                },
                $shops
            )
        );

        return new Struct\TotalShippingCosts(array(
            'shops' => $shops,
            'isShippable' => $isShippable,
            'shippingCosts' => $netShippingCosts,
            'grossShippingCosts' => $grossShippingCosts,
        ));
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
     * @param Struct\ProductList $productList
     * @return void
     */
    public function checkProducts(Struct\ProductList $productList)
    {
        $responses = array();
        $productLists = $this->zipProductListByShopId($productList);
        $myShopId = $this->config->getShopId();

        foreach ($productLists as $shopId => $products) {
            $shopGateway = $this->shopFactory->getShopGateway($shopId);
            $responses[$shopId] = $shopGateway->checkProducts($products, $myShopId);
        }

        $result = array();
        foreach ($responses as $shopId => $changes) {
            if ($changes !== true) {
                $this->applyRemoteShopChanges($changes);
                $result = array_merge(
                    $result,
                    $this->changeVisitor->visit($changes)
                );
            }
        }

        return $result ?: true;
    }

    /**
     * Zip product list by shop Id
     *
     * @param Struct\ProductList $productList
     * @return Struct\ProductList[]
     */
    private function zipProductListByShopId(Struct\ProductList $productList)
    {
        $productLists = array();

        foreach ($productList->products as $product) {
            if (!isset($productLists[$product->shopId])) {
                $productLists[$product->shopId] = new Struct\ProductList();
            }

            $productLists[$product->shopId]->products[] = $product;
        }

        return $productLists;
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

        $shippingCosts = $this->calculateShippingCosts($order, Gateway\ShippingCosts::SHIPPING_COSTS_INTERSHOP);

        if (!$shippingCosts->isShippable) {
            return $this->failedReservationNotShippable($orders, $shippingCosts);
        }

        foreach ($orders as $shopId => $order) {
            $order->shippingCosts = $shippingCosts->shops[$shopId]->shippingCosts;
            $order->grossShippingCosts = $shippingCosts->shops[$shopId]->grossShippingCosts;
            $order->shippingRule = $shippingCosts->shops[$shopId]->rule;

            $shopGateway = $this->shopFactory->getShopGateway($shopId);
            $responses[$shopId] = $shopGateway->reserveProducts($this->anonymizeCustomerEmail($order));
        }

        $reservation = new Struct\Reservation();
        $reservation->orders = $orders;
        foreach ($responses as $shopId => $response) {
            if (is_string($response)) {
                $reservation->orders[$shopId]->reservationId = $response;
            } elseif (is_array($response)) {
                $this->applyRemoteShopChanges($response);
                $reservation->messages[$shopId] = $this->changeVisitor->visit($response);
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
        }

        return $remoteOrder;
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
                case $change instanceof Struct\Change\InterShop\Update:
                    $this->productToShop->insertOrUpdate($change->product);
                    break;
                case $change instanceof Struct\Change\InterShop\Delete:
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
