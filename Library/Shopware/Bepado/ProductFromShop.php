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

namespace Shopware\Bepado;
use Bepado\SDK\ProductFromShop as ProductFromShopBase,
    Bepado\SDK\Struct\Order,
    Bepado\SDK\Struct\Product,
    Shopware\Models\Order as OrderModel,
    Shopware\Components\Model\ModelManager,
    Doctrine\ORM\Query;

/**
 * @category  Shopware
 * @package   Shopware\Plugins\SwagBepado
 * @copyright Copyright (c) 2013, shopware AG (http://www.shopware.de)
 * @author    Heiner Lohaus
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
     * @return string[]
     */
    public function getExportedProductIDs()
    {
        $repository = $this->manager->getRepository(
            'Shopware\Models\Article\Article'
        );
        $builder = $repository->createQueryBuilder('a');
        $builder->join('a.mainDetail', 'd');
        $builder->join('d.attribute', 'at');
        $builder->where("at.bepadoExportStatus IN ('online', 'update', 'insert')");
        $builder->select(array(
            'a.id as sourceId'
        ));
        $query = $builder->getQuery();
        $ids = $query->getArrayResult();
        $ids = array_map(function($id) {
            return $id['sourceId'];
        }, $ids);
        return $ids;
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
     * Buy products mentioned in order
     *
     * Should return the internal order ID.
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
        $detailStatus = $this->manager->find('Shopware\Models\Order\DetailStatus', 0);
        $status = $this->manager->find('Shopware\Models\Order\Status', 0);
        $number = 'BP-' . $order->orderShop . '-' . $order->localOrderId;

        //$model = new OrderModel\Order();
        $sql = 'INSERT INTO `s_order` (`ordernumber`, `cleared`) VALUES (?, 12);';
        Shopware()->Db()->query($sql, array($number));
        $modelId = Shopware()->Db()->lastInsertId();
        $model = $this->manager->find('Shopware\Models\Order\Order', $modelId);

        $model->fromArray(array(
            'number' => $number,
            'invoiceShipping' => $order->shippingCosts,
            'invoiceShippingNet' => $order->shippingCosts,
            'currencyFactor' => 1,
            'orderStatus' => $status,
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
                'price' => $product->product->price,
                'taxRate' => $product->product->vat * 100,
                'status' => $detailStatus
            ));
            $items[] = $item;
            $productDetail->setInStock($productDetail->getInStock() - $product->count);
        }
        $model->setDetails($items);

        $hash = md5(serialize($order->deliveryAddress));
        $email = substr($hash, 0, 8) . '@bepado.de';

        $repository = $this->manager->getRepository('Shopware\Models\Customer\Customer');
        $customer = $repository->findOneBy(array(
            'email' => $email,
            'hashPassword' => $hash
        ));
        if($customer === null) {
            $customer = new \Shopware\Models\Customer\Customer();
            $customer->fromArray(array(
                'active' => true,
                'email' => $email,
                'hashPassword' => $hash,
                'accountMode' => 1
            ));
            $this->manager->persist($customer);
        }

        $model->setCustomer($customer);

        $billing = new OrderModel\Billing();
        $billing->fromArray(array(
            'salutation' => 'mr',
            'lastName' => $order->deliveryAddress->name,
            'city' => $order->deliveryAddress->city,
            'zipCode' => $order->deliveryAddress->zip,
            'street' => $order->deliveryAddress->line1
        ));
        $model->setBilling($billing);

        $shipping = new OrderModel\Shipping();
        $shipping->fromArray(array(
            'salutation' => 'mr',
            'lastName' => $order->deliveryAddress->name,
            'city' => $order->deliveryAddress->city,
            'zipCode' => $order->deliveryAddress->zip,
            'street' => $order->deliveryAddress->line1
        ));
        $model->setShipping($shipping);

        $model->calculateInvoiceAmount();

        //$this->manager->persist($model);
        $this->manager->flush();

        return $model->getId();
    }
}