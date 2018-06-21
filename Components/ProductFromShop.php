<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Components;

use Enlight_Event_EventManager;

use Shopware\Connect\Gateway;
use Shopware\Connect\ProductFromShop as ProductFromShopBase;
use Shopware\Connect\Struct\Order;
use Shopware\Connect\Struct\Product;
use Shopware\Connect\Struct\Address as ConnectAddress;
use Shopware\Connect\Struct\Change\FromShop\Availability;
use Shopware\Connect\Struct\Change\FromShop\Insert;
use Shopware\Connect\Struct\Change\FromShop\Update;
use Shopware\Connect\Struct\PaymentStatus;
use Shopware\Connect\Struct\Shipping;

use Shopware\Models\Shop\Shop;
use Shopware\Models\Order\Order as ShopwareOrder;
use Shopware\Models\Customer\Address as ShopwareAddress;
use Shopware\Models\Dispatch\Dispatch;
use Shopware\Models\Order as OrderModel;
use Shopware\Models\Attribute\OrderDetail as OrderDetailAttributeModel;
use Shopware\Models\Customer as CustomerModel;

use Shopware\Components\Model\ModelManager;
use Shopware\Components\Random;

use ShopwarePlugins\Connect\Components\ProductStream\ProductStreamService;
use Shopware\CustomModels\Connect\Attribute;
use Shopware\Connect\Struct\Message;

/**
 * The interface for products exported *to* connect *from* the local shop
 *
 * @category  Shopware
 * @package   Shopware\Plugins\SwagConnect
 */
class ProductFromShop implements ProductFromShopBase
{
    /**
     * @var Helper
     */
    private $helper;

    /**
     * @var ModelManager
     */
    private $manager;

    /**
     * @var \Shopware\Connect\Gateway
     */
    private $gateway;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var Enlight_Event_EventManager
     */
    private $eventManager;

    /**
     * @param Helper $helper
     * @param ModelManager $manager
     * @param Gateway $gateway
     * @param Logger $logger
     * @param Enlight_Event_EventManager $eventManager
     */
    public function __construct(
        Helper $helper,
        ModelManager $manager,
        Gateway $gateway,
        Logger $logger,
        Enlight_Event_EventManager $eventManager
    ) {
        $this->helper = $helper;
        $this->manager = $manager;
        $this->gateway = $gateway;
        $this->logger = $logger;
        $this->eventManager = $eventManager;
    }

    /**
     * Get product data
     *
     * Get product data for all the source IDs specified in the given string
     * array.
     *
     * @param string[] $sourceIds
     * @return Product[]
     */
    public function getProducts(array $sourceIds)
    {
        return $this->helper->getLocalProduct($sourceIds);
    }

    /**
     * Get all IDs of all exported products
     *
     * @throws \BadMethodCallException
     * @return string[]
     */
    public function getExportedProductIDs()
    {
        throw new \BadMethodCallException('Not implemented');
    }

    /**
     * Reserve a product in shop for purchase
     *
     * @param Order $order
     * @throws \Exception Abort reservation by throwing an exception here.
     * @return void
     */
    public function reserve(Order $order)
    {
        $this->eventManager->notify(
            'Connect_Supplier_Reservation_Before',
            [
                'subject' => $this,
                'order' => $order
            ]
        );
    }

    /**
     * Create order in shopware
     * Wraps the actual order process into a transaction
     *
     *
     * @param Order $order
     * @throws \Exception Abort buy by throwing an exception,
     *                    but only in very important cases.
     *                    Do validation in {@see reserve} instead.
     * @return string
     */
    public function buy(Order $order)
    {
        $this->manager->beginTransaction();
        try {
            $order = $this->eventManager->filter('Connect_Components_ProductFromShop_Buy_OrderFilter', $order);

            $this->validateBilling($order->billingAddress);
            $orderNumber = $this->doBuy($order);

            $this->manager->commit();
        } catch (\Exception $e) {
            $this->manager->rollback();
            throw $e;
        }

        return $orderNumber;
    }

    /**
     * returns PaymentInformation for invoice
     * @return null|object|\Shopware\Models\Payment\Payment
     */
    private function getInvoicePayment()
    {
        $repository = $this->manager->getRepository('Shopware\Models\Payment\Payment');
        return $repository->findOneBy([
            'name' => 'invoice',
        ]);
    }



    /**
     * Actually creates the remote order in shopware.
     *
     * @param Order $order
     * @return string
     */
    public function doBuy(Order $order)
    {
        $this->manager->clear();

        $shop = $this->manager->find('Shopware\Models\Shop\Shop', 1);
        $shopwareOrder = $this->createAndReturnMinimalOrderInShopware($order, $shop);

        $this->fillShopwareOrderWithItems($shopwareOrder, $order);

        $customer = $this->prepareCustomerFromConnectOrder($order, $shop);

        $shopwareOrder->setCustomer($customer);

        $billing = new OrderModel\Billing();
        $billing->setCustomer($customer);
        $billing->fromArray($this->getAddressData(
                $order->billingAddress
        ));
        $shopwareOrder->setBilling($billing);

        $shipping = new OrderModel\Shipping();
        $shipping->setCustomer($customer);
        $shipping->fromArray($this->getAddressData(
            $order->deliveryAddress
        ));
        $shopwareOrder->setShipping($shipping);

        $shopwareOrder->calculateInvoiceAmount();

        $dispatchRepository = $this->manager->getRepository('Shopware\Models\Dispatch\Dispatch');
        $dispatch = $dispatchRepository->findOneBy([
            'name' => $order->shipping->service
        ]);
        if ($dispatch) {
            $shopwareOrder->setDispatch($dispatch);
        }

        $this->eventManager->notify(
            'Connect_Supplier_Buy_Before',
            [
                'subject' => $this,
                'order' => $order
            ]
        );

        $this->manager->flush();

        return $shopwareOrder->getNumber();
    }

    /**
     * @param Order $connectOrder
     * @param Shop $shop
     * @return ShopwareOrder
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     * @throws \Zend_Db_Adapter_Exception
     */
    private function createAndReturnMinimalOrderInShopware(Order $connectOrder, Shop $shop)
    {
        $orderNumber = sprintf('SC-%s-%s',
            $connectOrder->orderShop,
            $connectOrder->localOrderId
        );
        $status = $this->manager->find('Shopware\Models\Order\Status', 0);

        // todo: Create the OrderModel without previous plain SQL
        //$model = new OrderModel\Order();
        $sql = 'INSERT INTO `s_order` (`ordernumber`, `cleared`) VALUES (?, 17);';
        Shopware()->Db()->query($sql, [$orderNumber]);
        $modelId = Shopware()->Db()->lastInsertId();

        /** @var $shopwareOrder \Shopware\Models\Order\Order */
        $shopwareOrder = $this->manager->find('Shopware\Models\Order\Order', $modelId);

        $attribute = new \Shopware\Models\Attribute\Order;
        $attribute->setConnectOrderId($connectOrder->localOrderId);
        $attribute->setConnectShopId($connectOrder->orderShop);
        $shopwareOrder->setAttribute($attribute);

        $shopwareOrder->fromArray([
            'number' => $orderNumber,
            'invoiceShipping' => $connectOrder->shipping->grossShippingCosts,
            'invoiceShippingNet' => $connectOrder->shipping->shippingCosts,
            'currencyFactor' => 1,
            'orderStatus' => $status,
            'shop' => $shop,
            'languageSubShop' => $shop,
            'payment' => $this->getInvoicePayment(),
            'currency' => 'EUR',
            'orderTime' => 'now'
        ]);

        return $shopwareOrder;
    }

    /**
     * iterates through order items and converts them to positions in shopware order
     * @param ShopwareOrder $shopwareOrder
     * @param Order $connectOrder
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    private function fillShopwareOrderWithItems(ShopwareOrder $shopwareOrder, Order $connectOrder)
    {
        $openDetailStatus = $this->manager->find('Shopware\Models\Order\DetailStatus', 0);
        $connectAttributeRepository = $this->manager->getRepository('Shopware\CustomModels\Connect\Attribute');

        $items = [];
        /** @var \Shopware\Connect\Struct\OrderItem $orderItem */
        foreach ($connectOrder->orderItems as $orderItem) {
            $product = $orderItem->product;
            /** @var \Shopware\CustomModels\Connect\Attribute $connectAttribute */
            //use null as shop id to fetch exported articles
            $connectAttribute = $connectAttributeRepository->findOneBy([
                'sourceId' => $product->sourceId,
                'shopId' => null,
            ]);
            if (!$connectAttribute) {
                $this->logger->write(
                    true,
                    sprintf('Detail with sourceId: %s does not exist', $product->sourceId),
                    null,
                    true
                );
                continue;
            }

            /** @var $detail \Shopware\Models\Article\Detail */
            $detail = $connectAttribute->getArticleDetail();
            /** @var $productModel \Shopware\Models\Article\Article */
            $productModel = $detail->getArticle();
            $item = new OrderModel\Detail();
            $item->fromArray([
                'articleId' => $productModel->getId(),
                'quantity' => $orderItem->count,
                'orderId' => $shopwareOrder->getId(),
                'number' => $shopwareOrder->getNumber(),
                'articleNumber' => $detail->getNumber(),
                'articleName' => $product->title,
                'price' => $this->calculatePrice($product),
                'taxRate' => $product->vat * 100,
                'status' => $openDetailStatus,
                'attribute' => new OrderDetailAttributeModel()
            ]);
            $items[] = $item;
        }
        $shopwareOrder->setDetails($items);
    }

    /**
     * Reads customer information from connectOrder and creates a new customer if it does not exists
     * @param Order $connectOrder
     * @param Shop $shop
     * @return CustomerModel\Customer
     */
    private function prepareCustomerFromConnectOrder(Order $connectOrder, Shop $shop)
    {
        $email = $connectOrder->billingAddress->email;

        $customerRepository = $this->manager->getRepository('Shopware\Models\Customer\Customer');

        /** @var CustomerModel\Customer $customer */
        $customer = $customerRepository->findOneBy([
            'email' => $email
        ]);

        if ($customer === null) {
            $password = Random::getAlphanumericString(30);
            $customer = new CustomerModel\Customer();
            $customer->fromArray([
                'active' => true,
                'email' => $email,
                'password' => $password,
                'accountMode' => 1,
                'shop' => $shop,
                'paymentId' => $this->getInvoicePayment()->getId(),
            ]);
            //we need to save the customer right now to receive a id
            $this->manager->persist($customer);
            $this->manager->flush($customer);
        }

        $billingAddress = $customer->getDefaultBillingAddress();
        if ($billingAddress === null) {
            $billingAddress = new ShopwareAddress();
        }

        $billingAddress->fromArray($this->getAddressData(
            $connectOrder->billingAddress
        ));
        $billingAddress->setCustomer($customer);
        $this->manager->persist($billingAddress);

        $customer->setDefaultBillingAddress($billingAddress);

        $this->manager->persist($customer);

        return $customer;
    }

    /**
     * Calculate the price (including VAT) that the from shop needs to pay.
     *
     * This is most likely NOT the price the customer itself has to pay.
     *
     * @return float
     */
    private function calculatePrice($product)
    {
        return $product->purchasePrice * ($product->vat + 1);
    }

    /**
     * @param ConnectAddress $address
     * @return array
     */
    private function getAddressData(ConnectAddress $address)
    {
        $repository = 'Shopware\Models\Country\Country';
        $repository = $this->manager->getRepository($repository);
        /** @var $country \Shopware\Models\Country\Country */
        $country = $repository->findOneBy([
            'iso3' => $address->country
        ]);

        return [
            'company' => $address->company ?: '',
            'department' => $address->department ?: '',
            'additionalAddressLine1' => $address->additionalAddressLine1 ?: '',
            'additionalAddressLine2' => $address->additionalAddressLine2 ?: '',
            'salutation' => 'mr',
            'lastName' => $address->surName,
            'firstName' => $address->firstName,
            'city' => $address->city,
            'zipCode' => $address->zip,
            'street' => $address->street,
            'streetNumber' => $address->streetNumber,
            'phone' => $address->phone,
            'country' => $country
        ];
    }

    public function updatePaymentStatus(PaymentStatus $status)
    {
        // $paymentStatus->localOrderId is actually ordernumber for this shop
        // e.g. BP-35-20002
        $repository = $this->manager->getRepository('Shopware\Models\Order\Order');
        $order = $repository->findOneBy(['number' => $status->localOrderId]);

        if ($order) {
            $paymentStatusRepository = $this->manager->getRepository('Shopware\Models\Order\Status');
            /** @var \Shopware\Models\Order\Status $orderPaymentStatus */
            $orderPaymentStatus = $paymentStatusRepository->findOneBy(
                ['name' => 'sc_' . $status->paymentStatus]
            );

            if ($orderPaymentStatus) {
                $order->setPaymentStatus($orderPaymentStatus);

                $this->eventManager->notify(
                    'Connect_Supplier_Update_PaymentStatus_Before',
                    [
                        'subject' => $this,
                        'paymentStatus' => $status,
                        'order' => $order
                    ]
                );

                $this->manager->persist($order);
                $this->manager->flush();
            } else {
                $this->logger->write(
                    true,
                    sprintf(
                        'Payment status "%s" not found',
                        $status->paymentStatus
                    ),
                    sprintf(
                        'Order with id "%s"',
                        $status->localOrderId
                    )
                );
            }
        } else {
            $this->logger->write(
                true,
                sprintf(
                    'Order with id "%s" not found',
                    $status->localOrderId
                ),
                serialize($status)
            );
        }
    }

    public function calculateShippingCosts(Order $order)
    {
        if (!$order->deliveryAddress) {
            return new Shipping([
                'isShippable' => false,
                'messages' => [
                    new Message([
                        'message' => 'delivery_address_empty'
                    ])
                ]
            ]);
        }

        $countryIso3 = $order->deliveryAddress->country;
        $country = $this->manager->getRepository('Shopware\Models\Country\Country')->findOneBy(['iso3' => $countryIso3]);

        if (!$country) {
            return new Shipping([
                'isShippable' => false,
                'messages' => [
                    new Message([
                        'message' => 'order_not_shippable_to_country',
                        'values' => [
                            'country' => $countryIso3,
                        ]
                    ])
                ]
            ]);
        }

        if (count($order->orderItems) == 0) {
            throw new \InvalidArgumentException(
                'ProductList is not allowed to be empty'
            );
        }

        /* @var \Shopware\Models\Shop\Shop $shop */
        $shop = $this->manager->getRepository('Shopware\Models\Shop\Shop')->getActiveDefault();
        if (!$shop) {
            return new Shipping([
                'isShippable' => false,
                'messages' => [
                    new Message([
                        'message' => 'default_shop_not_found'
                    ])
                ]
            ]);
        }
        $shop->registerResources(Shopware()->Container()->get('bootstrap'));

        /** @var /Enlight_Components_Session_Namespace $session */
        $session = Shopware()->Session();
        $sessionId = uniqid('connect_remote');
        $session->offsetSet('sSESSION_ID', $sessionId);

        $repository = $this->manager->getRepository('Shopware\CustomModels\Connect\Attribute');
        $products = [];
        /** @var \Shopware\Connect\Struct\OrderItem $orderItem */
        foreach ($order->orderItems as $orderItem) {
            $attributes = $repository->findBy(['sourceId' => [$orderItem->product->sourceId], 'shopId' => null]);
            if (count($attributes) === 0) {
                continue;
            }

            $products[] = [
                'ordernumber' => $attributes[0]->getArticleDetail()->getNumber(),
                'quantity' => $orderItem->count,
            ];
        }

        /** @var \Shopware\CustomModels\Connect\Attribute $attribute */
        foreach ($products as $product) {
            Shopware()->Modules()->Basket()->sAddArticle($product['ordernumber'], $product['quantity']);
        }

        $shippingMethods = Shopware()->Modules()->Admin()->sGetPremiumDispatches($country->getId());
        if (empty($shippingMethods)) {
            return new Shipping([
                'isShippable' => false,
                'messages' => [
                    new Message([
                        'message' => 'order_not_shippable_to_country',
                        'values' => [
                            'country' => $countryIso3,
                        ]
                    ])
                ]
            ]);
        }

        $shippingMethod = reset($shippingMethods);

        /** @var Dispatch $shipping */
        $shipping = $this->manager->getRepository(Dispatch::class)->find($shippingMethod['id']);

        if (!$shipping) {
            return new Shipping([
                'isShippable' => false,
                'messages' => [
                    new Message([
                        'message' => 'default_shipping_not_found'
                    ])
                ]
            ]);
        }

        $session->offsetSet('sDispatch', $shipping->getId());

        $result = Shopware()->Modules()->Admin()->sGetPremiumShippingcosts(['id' => $country->getId()]);
        if (!is_array($result)) {
            return new Shipping([
                'isShippable' => false,
                'messages' => [
                    new Message([
                        'message' => 'checkout_not_possible'
                    ])
                ]
            ]);
        }

        $sql = 'DELETE FROM s_order_basket WHERE sessionID=?';
        Shopware()->Db()->executeQuery($sql, [
            $sessionId,
        ]);

        $shippingReturn = new Shipping([
            'shopId' => $this->gateway->getShopId(),
            'service' => $shipping->getName(),
            'shippingCosts' => floatval($result['netto']),
            'grossShippingCosts' => floatval($result['brutto']),
        ]);

        $this->eventManager->notify(
            'Connect_Supplier_Get_Shipping_After',
            [
                'subject' => $this,
                'shipping' => $shippingReturn,
                'order' => $order
            ]
        );

        return $shippingReturn;
    }

    /**
     * Perform sync changes to fromShop
     *
     * @param string $since
     * @param \Shopware\Connect\Struct\Change[] $changes
     * @return void
     */
    public function onPerformSync($since, array $changes)
    {
        $this->manager->getConnection()->beginTransaction();

        $statusSynced = Attribute::STATUS_SYNCED;
        $statusInsert = Attribute::STATUS_INSERT;
        $statusUpdate = Attribute::STATUS_UPDATE;
        $statusDelete = Attribute::STATUS_DELETE;
        try {
            if ($since) {
                $this->manager->getConnection()->executeQuery(
                    'UPDATE s_plugin_connect_items
                    SET export_status = ?
                    WHERE revision <= ?
                    AND ( export_status = ? OR export_status = ? )',
                    [$statusSynced, $since, $statusInsert, $statusUpdate]
                );

                $this->manager->getConnection()->executeQuery(
                    'UPDATE s_plugin_connect_items
                    SET export_status = ?
                    WHERE revision <= ?
                    AND export_status = ?',
                    [null, $since, $statusDelete]
                );
            } else {
                $this->manager->getConnection()->executeQuery(
                    'UPDATE s_plugin_connect_items
                    SET export_status = ?
                    WHERE revision IS NULL
                    AND ( export_status = ? OR export_status = ? )',
                    [$statusSynced, $statusInsert, $statusUpdate]
                );

                $this->manager->getConnection()->executeQuery(
                    'UPDATE s_plugin_connect_items
                    SET export_status = ?
                    WHERE revision IS NULL
                    AND export_status = ?',
                    [null, $statusDelete]
                );
            }


            /** @var \Shopware\Connect\Struct\Change $change */
            foreach ($changes as $change) {
                if (!$change instanceof Insert && !$change instanceof Update && !$change instanceof Availability) {
                    continue;
                }

                $this->manager->getConnection()->executeQuery(
                    'UPDATE s_plugin_connect_items
                    SET revision = ?
                    WHERE source_id = ? AND shop_id IS NULL',
                    [$change->revision, $change->sourceId]
                );
            }

            $this->manager->getConnection()->commit();
        } catch (\Exception $e) {
            $this->manager->getConnection()->rollBack();
        }

        try {
            $this->markStreamsAsSynced();
            $this->markStreamsAsNotExported();
        } catch (\Exception $e) {
            $this->logger->write(
                true,
                sprintf('Failed to mark streams as synced! Message: "%s". Trace: "%s"', $e->getMessage(), $e->getTraceAsString()),
                null
            );
        }
    }

    private function markStreamsAsNotExported()
    {
        $streamIds = $this->manager->getConnection()->executeQuery(
            'SELECT pcs.stream_id as streamId
             FROM s_plugin_connect_streams as pcs
             WHERE export_status = ?',
            [ProductStreamService::STATUS_DELETE]
        )->fetchAll();

        foreach ($streamIds as $stream) {
            $streamId = $stream['streamId'];

            $notDeleted = $this->manager->getConnection()->executeQuery(
                'SELECT pss.id
                 FROM s_product_streams_selection as pss
                 JOIN s_plugin_connect_items as pci
                 ON pss.article_id = pci.article_id
                 WHERE pss.stream_id = ?
                 AND pci.export_status != ?',
                [$streamId, null]
            )->fetchAll();

            if (count($notDeleted) === 0) {
                $this->manager->getConnection()->executeQuery(
                    'UPDATE s_plugin_connect_streams
                     SET export_status = ?
                     WHERE stream_id = ?',
                    [null, $streamId]
                );
            }
        }
    }

    private function markStreamsAsSynced()
    {
        $streamIds = $this->manager->getConnection()->executeQuery(
            'SELECT pcs.stream_id as streamId
             FROM s_plugin_connect_streams as pcs
             WHERE export_status = ?',
            [ProductStreamService::STATUS_EXPORT]
        )->fetchAll();

        foreach ($streamIds as $stream) {
            $streamId = $stream['streamId'];

            $notExported = $this->manager->getConnection()->executeQuery(
                'SELECT pss.id
                 FROM s_product_streams_selection as pss
                 JOIN s_plugin_connect_items as pci
                 ON pss.article_id = pci.article_id
                 WHERE pss.stream_id = ?
                 AND pci.export_status != ?',
                [$streamId, Attribute::STATUS_SYNCED]
            )->fetchAll();

            if (count($notExported) === 0) {
                $this->manager->getConnection()->executeQuery(
                    'UPDATE s_plugin_connect_streams
                     SET export_status = ?
                     WHERE stream_id = ?',
                    [ProductStreamService::STATUS_SYNCED, $streamId]
                );
            }
        }
    }

    /**
     * @param ConnectAddress $address
     */
    private function validateBilling(ConnectAddress $address)
    {
        if (!$address->email) {
            throw new \RuntimeException('Billing address should contain email');
        }

        if (!$address->firstName) {
            throw new \RuntimeException('Billing address should contain first name');
        }

        if (!$address->surName) {
            throw new \RuntimeException('Billing address should contain last name');
        }

        if (!$address->zip) {
            throw new \RuntimeException('Billing address should contain zip');
        }

        if (!$address->city) {
            throw new \RuntimeException('Billing address should contain city');
        }

        if (!$address->phone) {
            throw new \RuntimeException('Billing address should contain phone');
        }
    }
}
