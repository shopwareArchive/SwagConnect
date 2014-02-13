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

namespace Shopware\Bepado\Components;
use Bepado\SDK\ProductFromShop as ProductFromShopBase,
    Bepado\SDK\Struct\Order,
    Bepado\SDK\Struct\Product,
    Bepado\SDK\Struct\Address,
    Shopware\Models\Order as OrderModel,
    Shopware\Models\Customer as CustomerModel,
    Shopware\Components\Model\ModelManager,
    Doctrine\ORM\Query;

/**
 * The interface for products exported *to* bepado *from* the local shop
 *
 * @category  Shopware
 * @package   Shopware\Plugins\SwagBepado
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
     * @param Helper $helper
     * @param ModelManager $manager
     */
    public function __construct(Helper $helper, ModelManager $manager)
    {
        $this->helper = $helper;
        $this->manager = $manager;
    }

    /**
     * Get product data
     *
     * Get product data for all the product IDs specified in the given string
     * array.
     *
     * @param string[] $ids
     * @return Product[]
     */
    public function getProducts(array $ids)
    {
        $products = array();
        foreach($ids as $id) {
            $products[] = $this->helper->getProductById($id);
        }
        return $products;
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
        try {
            $this->manager->getConnection()->beginTransaction();
            $orderNumber = $this->buy($order);
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
        $detailStatus = $this->manager->find('Shopware\Models\Order\DetailStatus', 0);
        $status = $this->manager->find('Shopware\Models\Order\Status', 0);
        $shop = $this->manager->find('Shopware\Models\Shop\Shop', 1);
        $number = 'BP-' . $order->orderShop . '-' . $order->localOrderId;

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
        $attribute->setBepadoOrderId($order->localOrderId);
        $attribute->setBepadoShopId($order->orderShop);
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
        foreach($order->products as $product) {
            /** @var $productModel \Shopware\Models\Article\Article */
            $productModel = $this->manager->find(
                '\Shopware\Models\Article\Article',
                $product->product->sourceId
            );
            $productDetail = $productModel->getMainDetail();
            $item = new OrderModel\Detail();
            $item->fromArray(array(
                'articleId' => $product->product->sourceId,
                'quantity' => $product->count,
                'orderId' => $model->getId(),
                'number' => $model->getNumber(),
                'articleNumber' => $productDetail->getNumber(),
                'articleName' => $product->product->title,
                'price' => $this->calculatePrice($product->product),
                'taxRate' => $product->product->vat * 100,
                'status' => $detailStatus
            ));
            $items[] = $item;
        }
        $model->setDetails($items);

        $email = $order->deliveryAddress->email;

        $hash = md5(serialize($order->deliveryAddress));
        if (!$email) {
            // todo@dn: Use real mail address here.600
            $email = substr($hash, 0, 8) . '@bepado.de';
        }


        $repository = $this->manager->getRepository('Shopware\Models\Customer\Customer');
        $customer = $repository->findOneBy(array(
            'email' => $email,
            'hashPassword' => $hash
        ));
        if($customer === null) {
            $customer = new CustomerModel\Customer();
            $customer->fromArray(array(
                'active' => true,
                'email' => $email,
                'rawPassword' => $hash,
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
            $order->deliveryAddress
        ));
        $this->manager->persist($customer);

        $model->setCustomer($customer);

        $billing = new OrderModel\Billing();
        $billing->setCustomer($customer);
        $billing->fromArray($this->getAddressData(
            $order->deliveryAddress
        ));
        $model->setBilling($billing);

        $shipping = new OrderModel\Shipping();
        $shipping->setCustomer($customer);
        $shipping->fromArray($this->getAddressData(
            $order->deliveryAddress
        ));
        $model->setShipping($shipping);

        $model->calculateInvoiceAmount();

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
}
