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
    Shopware\Models\Article\Detail as DetailModel,
    Shopware\Models\Attribute\Article as AttributeModel,
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
        $repository = Shopware()->Models()->getRepository(
            'Shopware\Models\Article\Article'
        );
        $builder = $repository->createQueryBuilder('a');
        $builder->select(array('a', 'd', 'at'));
        $builder->join('a.details', 'd');
        $builder->leftJoin('d.attribute', 'at');
        $builder->where('at.bepadoShopId = :shopId AND at.bepadoSourceId = :sourceId');
        $builder->orWhere('d.number = :number');
        $query = $builder->getQuery();
        $query->setParameter('shopId', $product->shopId);
        $query->setParameter('sourceId', $product->sourceId);
        $query->setParameter('number', 'BP-' . $product->shopId . '-' . $product->sourceId);
        $result = $query->getResult(
            $query::HYDRATE_OBJECT,
            $mode
        );
        return isset($result[0]) ? $result[0] : null;
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
        if(empty($product->title) || empty($product->vendor)) {
            return;
        }
        $model = $this->getModelByProduct($product);
        if($model === null) {
            $model = new ProductModel();
            $detail = new DetailModel();
            $detail->setNumber('BP-' . $product->shopId . '-' . $product->sourceId);
            $model->setDetails(array($detail));
            $this->manager->persist($model);
        } else {
            $detail = $model->getDetails()->first();
        }
        $attribute = $detail->getAttribute() ?: new AttributeModel();

        if($product->vat !== null) {
            $repo = $this->manager->getRepository('Shopware\Models\Tax\Tax');
            $tax = round($product->vat * 100, 2);
            $tax = $repo->findOneBy(array('tax' => $tax));
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
        $attribute->setBepadoShopId($product->shopId);
        $attribute->setBepadoSourceId($product->sourceId);
        $attribute->setBepadoCategories(serialize($product->categories));
        $detail->setInStock($product->availability);

        //$model->setImages(array(
        //));

        $this->manager->flush($model);

        if($model->getMainDetail() === null) {
            $model->setMainDetail($detail);
            $this->manager->flush();
        }
        if($detail->getAttribute() === null) {
            $detail->setAttribute($attribute);
            $attribute->setArticle($model);
            $this->manager->flush();
        }
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
        $model->getDetails()->clear();
        $this->manager->remove($model);
        $this->manager->flush();
    }
}