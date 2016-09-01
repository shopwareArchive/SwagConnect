<?php
/**
 * Shopware 4.0
 * Copyright © 2013 shopware AG
 *
 * According to our dual licensing model, this program can be used either
 * under the terms of the GNU Affero General Public License, version 3,
 * or under a proprietary license.
 *
 * The texts of the GNU Affero General Public License with an additional
 * permission and of our proprietary license can be found at and
 * in the LICENSE file you have received along with this program.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * "Shopware" is a registered trademark of shopware AG.
 * The licensing of the program under the AGPLv3 does not imply a
 * trademark license. Therefore any rights, title and interest in
 * our trademarks remain entirely with us.
 */

namespace ShopwarePlugins\Connect\Components;
use Shopware\Connect\Gateway;
use Shopware\Connect\ProductFromShop as ProductFromShopBase,
    Shopware\Connect\Struct\Order,
    Shopware\Connect\Struct\Product,
    Shopware\Connect\Struct\Address,
    Shopware\Models\Order as OrderModel,
    Shopware\Models\Attribute\OrderDetail as OrderDetailAttributeModel,
    Shopware\Models\Customer as CustomerModel,
    Shopware\Components\Model\ModelManager,
    Doctrine\ORM\Query,
    Shopware\Components\Random;
use Shopware\Connect\Struct\Change\FromShop\Availability;
use Shopware\Connect\Struct\Change\FromShop\Insert;
use Shopware\Connect\Struct\Change\FromShop\Update;
use Shopware\Connect\Struct\PaymentStatus;
use Shopware\Connect\Struct\Shipping;

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
     * @param Helper $helper
     * @param ModelManager $manager
     */
    public function __construct(
        Helper $helper,
        ModelManager $manager,
        Gateway $gateway,
        Logger $logger
    )
    {
        $this->helper = $helper;
        $this->manager = $manager;
        $this->gateway = $gateway;
        $this->logger = $logger;
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
     * @return void
     * @throws \Exception Abort reservation by throwing an exception here.
     */
    public function reserve(Order $order)
    {
    }

    /**
     * Create order in shopware
     * Wraps the actual order process into a transaction
     *
     *
     * @param Order $order
     * @return string
     *
     * @throws \Exception Abort buy by throwing an exception,
     *                    but only in very important cases.
     *                    Do validation in {@see reserve} instead.
     */
    public function buy(Order $order)
    {
        $this->manager->getConnection()->beginTransaction();

        try {
            $this->validateBilling($order->billingAddress);
            $orderNumber = $this->doBuy($order);
            $this->manager->getConnection()->commit();
        } catch (\Exception $e) {
            $this->manager->getConnection()->rollBack();
            throw $e;
        }

        return $orderNumber;
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

        $detailStatus = $this->manager->find('Shopware\Models\Order\DetailStatus', 0);
        $status = $this->manager->find('Shopware\Models\Order\Status', 0);
        $shop = $this->manager->find('Shopware\Models\Shop\Shop', 1);
        $number = 'SC-' . $order->orderShop . '-' . $order->localOrderId;

        $repository = $this->manager->getRepository('Shopware\Models\Payment\Payment');
        $payment = $repository->findOneBy(array(
            'name' => 'invoice',
        ));

        // todo: Create the OrderModel without previous plain SQL
        //$model = new OrderModel\Order();
        $sql = 'INSERT INTO `s_order` (`ordernumber`, `cleared`) VALUES (?, 17);';
        Shopware()->Db()->query($sql, array($number));
        $modelId = Shopware()->Db()->lastInsertId();
        /** @var $model \Shopware\Models\Order\Order */
        $model = $this->manager->find('Shopware\Models\Order\Order', $modelId);

        $attribute = new \Shopware\Models\Attribute\Order;
        $attribute->setConnectOrderId($order->localOrderId);
        $attribute->setConnectShopId($order->orderShop);
        $model->setAttribute($attribute);

        $model->fromArray(array(
            'number' => $number,
            'invoiceShipping' => $order->grossShippingCosts,
            'invoiceShippingNet' => $order->shippingCosts,
            'currencyFactor' => 1,
            'orderStatus' => $status,
            'shop' => $shop,
            'languageSubShop' => $shop,
            'payment' => $payment,
            'currency' => 'EUR',
            'orderTime' => 'now'
        ));
        $items = array();
        $connectAttributeRepository = $this->manager->getRepository('Shopware\CustomModels\Connect\Attribute');

        /** @var \Shopware\Connect\Struct\OrderItem $orderItem */
        foreach($order->products as $orderItem) {
            $product = $orderItem->product;
            /** @var \Shopware\CustomModels\Connect\Attribute $connectAttribute */
            $connectAttribute = $connectAttributeRepository->findOneBy(array(
                'sourceId' => $product->sourceId,
                'shopId' => null,
            ));
            if (!$connectAttribute) {
                $this->logger->write(
                    true,
                    sprintf('Detail with sourceId: %s does not exist', $product->sourceId),
                    null
                );
                continue;
            }

            /** @var $detail \Shopware\Models\Article\Detail */
            $detail = $connectAttribute->getArticleDetail();
            /** @var $productModel \Shopware\Models\Article\Article */
            $productModel = $detail->getArticle();
            $item = new OrderModel\Detail();
            $item->fromArray(array(
                'articleId' => $productModel->getId(),
                'quantity' => $orderItem->count,
                'orderId' => $model->getId(),
                'number' => $model->getNumber(),
                'articleNumber' => $detail->getNumber(),
                'articleName' => $product->title,
                'price' => $this->calculatePrice($product),
                'taxRate' => $product->vat * 100,
                'status' => $detailStatus,
                'attribute' => new OrderDetailAttributeModel()
            ));
            $items[] = $item;
        }
        $model->setDetails($items);

        $email = $order->billingAddress->email;

        $password = Random::getAlphanumericString(30);

        $repository = $this->manager->getRepository('Shopware\Models\Customer\Customer');
        $customer = $repository->findOneBy(array(
            'email' => $email
        ));
        if($customer === null) {
            $customer = new CustomerModel\Customer();
            $customer->fromArray(array(
                'active' => true,
                'email' => $email,
                'password' => $password,
                'accountMode' => 1,
                'shop' => $shop,
                'paymentId' => $payment->getId(),
            ));
        }
        if($customer->getBilling() === null) {
            $billing = new CustomerModel\Billing();
            $customer->setBilling($billing);
        } else {
            $billing = $customer->getBilling();
        }

        $billing->fromArray($this->getAddressData(
            $order->billingAddress
        ));
        $this->manager->persist($customer);

        $model->setCustomer($customer);

        $billing = new OrderModel\Billing();
        $billing->setCustomer($customer);
        $billing->fromArray($this->getAddressData(
                $order->billingAddress
        ));
        $model->setBilling($billing);

        $shipping = new OrderModel\Shipping();
        $shipping->setCustomer($customer);
        $shipping->fromArray($this->getAddressData(
            $order->deliveryAddress
        ));
        $model->setShipping($shipping);

        $model->calculateInvoiceAmount();

        $dispatchRepository = $this->manager->getRepository('Shopware\Models\Dispatch\Dispatch');
        $dispatch = $dispatchRepository->findOneBy(array(
            'name' => $order->shipping->service
        ));
        if ($dispatch) {
            $model->setDispatch($dispatch);
        }

        $this->manager->flush();

        return $model->getNumber();
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
     * @param Address $address
     * @return array
     */
    private function getAddressData(Address $address)
    {
        $repository = 'Shopware\Models\Country\Country';
        $repository = $this->manager->getRepository($repository);
        /** @var $country \Shopware\Models\Country\Country */
        $country = $repository->findOneBy(array(
            'iso3' => $address->country
        ));
        return array(
            'company' => $address->company ?: '',
            'salutation' => 'mr',
            'lastName' => $address->surName,
            'firstName' => $address->firstName,
            'city' => $address->city,
            'zipCode' => $address->zip,
            'street' => $address->street,
            'streetNumber' => $address->streetNumber,
            'phone' => $address->phone,
            'country' => $country
        );
    }

    public function updatePaymentStatus(PaymentStatus $status)
    {
        // $paymentStatus->localOrderId is actually ordernumber for this shop
        // e.g. BP-35-20002
        $repository = $this->manager->getRepository('Shopware\Models\Order\Order');
        $order = $repository->findOneBy(array('number' => $status->localOrderId));

        if ($order) {
            $paymentStatusRepository = $this->manager->getRepository('Shopware\Models\Order\Status');
            /** @var \Shopware\Models\Order\Status $orderPaymentStatus */
            $orderPaymentStatus = $paymentStatusRepository->findOneBy(
                array('name' => 'sc_' . $status->paymentStatus)
            );

            if ($orderPaymentStatus) {
                $order->setPaymentStatus($orderPaymentStatus);

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
        $countryIso3 = $order->deliveryAddress->country;
        $country = Shopware()->Models()->getRepository('Shopware\Models\Country\Country')->findOneBy(array('iso3' => $countryIso3));

        if (!$country) {
            return new Shipping(array('isShippable' => false));
        }

        if (count($order->orderItems) == 0) {
            throw new \InvalidArgumentException(
                "ProductList is not allowed to be empty"
            );
        }

        /* @var \Shopware\Models\Shop\Shop $shop */
        $shop = Shopware()->Models()->getRepository('Shopware\Models\Shop\Shop')->getActiveDefault();
        if (!$shop) {
            return new Shipping(array('isShippable' => false));
        }
        $shop->registerResources(Shopware()->Bootstrap());

        $sessionId = uniqid('connect_remote');
        Shopware()->System()->sSESSION_ID = $sessionId;

        /** @var \Shopware\Models\Dispatch\Dispatch $shipping */
        $shipping = Shopware()->Models()->getRepository('Shopware\Models\Dispatch\Dispatch')->findOneBy(array(
            'type' => 0 // standard shipping
        ));

        // todo: if products are not shippable with default shipping
        // todo: do we need to check with other shipping methods
        if (!$shipping) {
            return new Shipping(array('isShippable' => false));
        }

        Shopware()->System()->_SESSION['sDispatch'] = $shipping->getId();

        $repository = Shopware()->Models()->getRepository('Shopware\CustomModels\Connect\Attribute');
        $products = array();
        /** @var \Shopware\Connect\Struct\OrderItem $orderItem */
        foreach ($order->orderItems as $orderItem) {
            $attributes = $repository->findBy(array('sourceId' => array($orderItem->product->sourceId), 'shopId' => null));
            if (count($attributes) === 0) {
                continue;
            }

            $products[] = array(
                'ordernumber' => $attributes[0]->getArticleDetail()->getNumber(),
                'quantity' => $orderItem->count,
            );
        }

        /** @var \Shopware\CustomModels\Connect\Attribute $attribute */
        foreach ($products as $product) {
            Shopware()->Modules()->Basket()->sAddArticle($product['ordernumber'], $product['quantity']);
        }

        $result = Shopware()->Modules()->Admin()->sGetPremiumShippingcosts(array('id' => $country->getId()));
        if (!is_array($result)) {
            return new Shipping(array('isShippable' => false));
        }

        $sql = 'DELETE FROM s_order_basket WHERE sessionID=?';
        Shopware()->Db()->executeQuery($sql, array(
            $sessionId,
        ));

        return new Shipping(array(
            'shopId' => $this->gateway->getShopId(),
            'service' => $shipping->getName(),
            'shippingCosts' => floatval($result['netto']),
            'grossShippingCosts' => floatval($result['brutto']),
        ));
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

        try {
            $this->manager->getConnection()->executeQuery(
                "UPDATE s_plugin_connect_items
                SET export_status = 'synced'
                WHERE revision <= ?",
                array($since)
            );

            /** @var \Shopware\Connect\Struct\Change $change */
            foreach ($changes as $change) {
                if (!$change instanceof Insert && !$change instanceof Update && !$change instanceof Availability) {
                    continue;
                }

                $this->manager->getConnection()->executeQuery(
                    "UPDATE s_plugin_connect_items
                    SET revision = ?
                    WHERE source_id = ? AND shop_id IS NULL",
                    array($change->revision, $change->sourceId)
                );
            }

            $this->manager->getConnection()->commit();
        } catch (\Exception $e) {
            $this->manager->getConnection()->rollBack();
        }
    }

    private function validateBilling(Address $address)
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
