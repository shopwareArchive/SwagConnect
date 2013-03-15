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
     * @var ModelManager
     */
    private $manager;

    /**
     * @param ModelManager $manager
     */
    public function __constructor(ModelManager $manager)
    {
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
        $repository = Shopware()->Models()->getRepository(
            'Shopware\Models\Article\Article'
        );
        $builder = $repository->createQueryBuilder('a');
        $builder->join('a.mainDetail', 'm');
        $builder->join('a.supplier', 's');
        $builder->select(array(
            'a.id as sourceId',
            'a.name as title',
            'a.description as longDescription',
            'a.shortDescription as shortDescription',
            's.name as vendor',
            'd.inStock as availability'
        ));
        $builder->where('a.id = ?');
        $query = $builder->getQuery();
        $products = array();
        foreach($ids as $id) {
            $product = $query->execute(array($id));
            $products[] = new Product($product);
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
            'number' => 'BP-' . $order->reservationId,
            'invoiceShipping' => $order->shippingCosts,
            'invoiceShippingNet' => $order->shippingCosts
        ));
        $this->manager->persist($model);
        $this->manager->flush($model);
        $items = array();
        foreach($order->products as $product) {
            $item = new OrderModel\Detail();
            $item->fromArray(array(
                'articleId' => $product->product->sourceId,
                'quantity' => $product->count,
                'orderId' => $model->getId()
            ));
            $items[] = $item;
        }
        $model->setDetails($items);
        $this->manager->flush($model);
        return $model->getId();
    }
}