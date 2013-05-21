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
        $model = new OrderModel\Order();
        $model->fromArray(array(
            'number' => 'BP-' . $order->orderShop . '-' . $order->localOrderId,
            'invoiceShipping' => $order->shippingCosts,
            'invoiceShippingNet' => $order->shippingCosts
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
                'price' => $product->product->purchasePrice,
                'taxRate' => $product->product->vat * 100
            ));
            $items[] = $item;
            $productDetail->setInStock($productDetail->getInStock() - $product->count);
        }
        $model->setDetails($items);

        $customer = new \Shopware\Models\Customer\Customer();
        $customer->fromArray(array(
            'active' => true,
            'accountMode' => 1
        ));
        $model->setCustomer($customer);

        $shipping = new OrderModel\Shipping();
        $shipping->fromArray(array(
            'lastName' => $order->deliveryAddress->name,
            'city' => $order->deliveryAddress->city,
            'zip' => $order->deliveryAddress->zip,
            'street' => $order->deliveryAddress->line1
        ));
        $model->setShipping($shipping);

        $this->manager->persist($model);
        $this->manager->flush();

        return $model->getId();
    }
}