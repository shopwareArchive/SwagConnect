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
use Bepado\SDK\ProductToShop as ProductToShopBase,
    Bepado\SDK\Struct\Product,
    Shopware\Models\Article\Article as ProductModel,
    Shopware\Components\Model\ModelManager,
    Doctrine\ORM\Query;

/**
 * @category  Shopware
 * @package   Shopware\Plugins\SwagBepado
 * @copyright Copyright (c) 2013, shopware AG (http://www.shopware.de)
 * @author    Heiner Lohaus
 */
class ProductToShop implements ProductToShopBase
{
    /**
     * @var ModelManager
     */
    private $manager;

    /**
     * @param ModelManager $manager
     */
    public function __construct(ModelManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * @param Product $product
     * @param int $mode
     * @return null|ProductModel
     */
    private function getModelByProduct(Product $product, $mode = Query::HYDRATE_OBJECT)
    {
        $number = $this->getNumberByProduct($product);
        $repository = Shopware()->Models()->getRepository(
            'Shopware\Models\Article\Article'
        );
        $builder = $repository->createQueryBuilder('a');
        $builder->join('a.mainDetail', 'd', 'with', 'd.number = :number');
        $query = $builder->getQuery();
        $query->setParameter('number', $number);
        $model = $query->getOneOrNullResult(
            $mode
        );
        return $model;
    }

    /**
     * @param Product $product
     * @return string
     */
    private function getNumberByProduct(Product $product)
    {
        return 'BP-' . $product->shopId . '-' . $product->sourceId;
    }

    /**
     * Import or update given product
     *
     * Store product in your shop database as an external product. The
     * associated sourceId
     *
     * @param Product $product
     */
    public function insertOrUpdate(Product $product)
    {
        if(empty($product->number) || empty($product->title) || empty($product->vendor)) {
            return;
        }
        $model = $this->getModelByProduct($product);
        if($model === null) {
            $model = new ProductModel();
            $model->setMainDetail(array(
                'number' => $this->getNumberByProduct($product)
            ));
            $this->manager->persist($model);
        }
        if($product->vat !== null) {
            $repo = $this->manager->getRepository('Shopware\Models\Tax\Tax');
            $tax = $repo->findOneBy(array('tax' => $product->vat));
            $model->setTax($tax);
        }
        if($product->vendor !== null) {
            $repo = $this->manager->getRepository('Shopware\Models\Article\Supplier');
            $supplier = $repo->findOneBy(array('name' => $product->vendor));
            if($supplier === null) {
                $supplier = new \Shopware\Models\Article\Supplier();
                $supplier->setName($product->vendor);
            }
            $model->setSupplier($supplier);
        }
        $model->setName($product->title);
        $model->setDescription($product->shortDescription);
        $model->setDescriptionLong($product->longDescription);
        /** @var $detail \Shopware\Models\Article\Detail */
        $detail = $model->getMainDetail();
        $detail->setInStock($product->availability);
        //$model->setImages(array(
        //));
        $this->manager->flush();
    }

    /**
     * Delete product with given shopId and sourceId.
     *
     * Only the combination of both identifies a product uniquely. Do NOT
     * delete products just by their sourceId.
     *
     * You might receive delete requests for products, which are not available
     * in your shop. Just ignore them.
     *
     * @param string $shopId
     * @param string $sourceId
     * @return void
     */
    public function delete($shopId, $sourceId)
    {
        $model = $this->getModelByProduct(new Product(array(
            'shopId' => $shopId,
            'sourceId' => $sourceId,
        )), Query::HYDRATE_SIMPLEOBJECT);
        if($model === null) {
            return;
        }
        $this->manager->remove($model);
        $this->manager->flush();
    }
}