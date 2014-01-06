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
use Exception;
use Bepado\SDK\SDK;
use Bepado\SDK\Struct\Product,
    Shopware\Models\Article\Article as ProductModel,
    Shopware\Models\Category\Category as CategoryModel,
    Shopware\Components\Model\ModelManager,
    Doctrine\ORM\Query;

/**
 * @category  Shopware
 * @package   Shopware\Plugins\SwagBepado
 * @copyright Copyright (c) 2013, shopware AG (http://www.shopware.de)
 * @author    Heiner Lohaus
 */
class Helper
{
    /**
     * @var ModelManager
     */
    private $manager;

    /**
     * @var string
     */
    private $imagePath, $productDescriptionField;

    /**
     * @var Query
     */
    private $modelQuery, $productQuery, $categoryQuery, $productCategoryQuery;

    private $bepadoCategoryQuery;

    /** @var  \Shopware\CustomModels\Bepado\ConfigRepository */
    private $configRepo;

    /**
     * @param ModelManager $manager
     * @param string $imagePath
     * @param string $productDescriptionField
     * @param CategoryQuery
     */
    public function __construct(ModelManager $manager, $imagePath, $productDescriptionField, $bepadoCategoryQuery)
    {
        $this->manager = $manager;
        $this->imagePath = $imagePath;
        $this->productDescriptionField = $productDescriptionField;
        $this->bepadoCategoryQuery = $bepadoCategoryQuery;
    }

    /**
     * @return \Shopware\CustomModels\Bepado\ConfigRepository
     */
    private function getConfigRepository()
    {
        if (!$this->configRepo) {
            $this->configRepo = $this->manager->getRepository('Shopware\CustomModels\Bepado\Config');
        }
        return $this->configRepo;
    }

    /**
     * @return \Shopware\Models\Article\Repository
     */
    private function getArticleRepository()
    {
        $repository = $this->manager->getRepository(
            'Shopware\Models\Article\Article'
        );
        return $repository;
    }

    /**
     * @return \Shopware\Models\Category\Repository
     */
    private function getCategoryRepository()
    {
        $repository = $this->manager->getRepository(
            'Shopware\Models\Category\Category'
        );
        return $repository;
    }

    /**
     * @return \Shopware\Models\Customer\Group
     */
    public function getDefaultCustomerGroup()
    {
        $repository = $this->manager->getRepository('Shopware\Models\Customer\Group');
        $customerGroup = $repository->findOneBy(array('key' => 'EK'));
        return $customerGroup;
    }

    /**
     * @return null|string
     */
    public function getProductDescriptionField()
    {
        return $this->productDescriptionField;
    }

    /**
     * This method covers the fromShop products as well as the toShop products.
     *
     * As the fields might differ in some situations, the result of this query needs to be
     * postprocessed by the getProductByRowData method.
     *
     * Currently there are three field being "switched" in the getProductByRowData method:
     * - price          (configurable in fromShop, d.price in toShop)
     * - purchasePrice  (configurable in from Shop, d.basePrice in toShop)
     * - freeDelivery   (d.shippingFree in fromShop, at.bepadoFreeDelivery in toShop)
     *
     * Two distinct queries?
     *
     * @return \Doctrine\ORM\Query
     */
    private function getProductQuery()
    {
        $repository = $this->getArticleRepository();
        $builder = $repository->createQueryBuilder('a');
        $builder->join('a.mainDetail', 'd');
        $builder->join('d.attribute', 'at');
        $builder->leftJoin('a.supplier', 's');
        $builder->leftJoin('d.prices', 'defaultPrice', 'with', "defaultPrice.from = 1 AND defaultPrice.customerGroupKey = 'EK'");
        $builder->join('a.tax', 't');
        $builder->leftJoin('d.unit', 'u');
        $builder->select(array(
            'at.bepadoShopId as shopId',
            'IFNULL(at.bepadoSourceId, a.id) as sourceId',
            'd.ean',
            'a.name as title',
            'a.description as shortDescription',
            'a.descriptionLong as longDescription',
            's.name as vendor',
            't.tax / 100 as vat',
            'defaultPrice.price as price',
            'defaultPrice.basePrice as purchasePrice',
            //'"EUR" as currency',
            'd.shippingFree as freeDelivery',
            '
            at.bepadoFreeDelivery as bepadoFreeDelivery',

            'd.releaseDate as deliveryDate',
            'd.inStock as availability',

            'd.width',
            'd.height',
            'd.len as length',

            'd.weight',
            'u.unit',
            'd.purchaseUnit as volume',
            'd.referenceUnit as base',
            'at.bepadoCategories as categories',
            'at.bepadoFixedPrice as fixedPrice'
            //'images = array()',
        ));



        $repo = $this->getConfigRepository();
        $exportPriceCustomerGroup = $repo->getConfig('priceGroupForPriceExport', 'EK');
        $exportPurchasePriceCustomerGroup = $repo->getConfig('priceGroupForPurchasePriceExport', 'EK');
        $exportPriceColumn = $repo->getConfig('priceFieldForPriceExport', 'price');
        $exportPurchasePriceColumn = $repo->getConfig('priceFieldForPurchasePriceExport', 'basePrice');

        $valid = $this->isPriceGroupConfigurationValid(
            $exportPriceColumn,
            $exportPurchasePriceColumn,
            $exportPurchasePriceCustomerGroup,
            $exportPriceCustomerGroup
        );

        if ($valid) {
            $builder->leftJoin('d.prices', 'exportPrice', 'with', "exportPrice.from = 1 AND exportPrice.customerGroupKey = 'EK'");
            $builder->leftJoin('d.prices', 'exportPurchasePrice', 'with', "exportPurchasePrice.from = 1 AND exportPurchasePrice.customerGroupKey = 'EK'");
            $builder->addSelect(array(
                "exportPrice.{$exportPriceColumn}  as bepadoExportPrice",
                "exportPurchasePrice.{$exportPurchasePriceColumn} as bepadoExportPurchasePrice"
            ));
        }

        if($this->productDescriptionField !== null) {
            $builder->addSelect('at.' . $this->productDescriptionField . ' as altDescription');
        }
        $builder->where('a.id = :id');
        $query = $builder->getQuery();

        $query->setHydrationMode($query::HYDRATE_ARRAY);
        return $query;
    }

    private function isPriceGroupConfigurationValid($exportPriceColumn, $exportPurchasePriceColumn,
        $exportPurchasePriceCustomerGroup, $exportPriceCustomerGroup)
    {
        if (empty($exportPriceColumn) || empty($exportPurchasePriceColumn)
                || empty($exportPriceCustomerGroup) || empty($exportPurchasePriceCustomerGroup)) {
            return false;
        }

        $validEntries = array('basePrice', 'pseudoPrice', 'price');
        if (!in_array($exportPriceColumn, $validEntries) || !in_array($exportPurchasePriceColumn, $validEntries)) {
            return false;
        }


        return true;
    }

    /**
     * @return \Doctrine\ORM\QueryBuilder
     */
    private function getArticleModelQueryBuilder()
    {
        $repository = $this->getArticleRepository();
        $builder = $repository->createQueryBuilder('a');
        $builder->select(array('a', 'd', 'at'));
        $builder->join('a.mainDetail', 'd');
        $builder->leftJoin('d.attribute', 'at');
        return $builder;
    }

    /**
     * @return \Doctrine\ORM\Query
     */
    private function getArticleModelByIdQuery()
    {
        $builder = $this->getArticleModelQueryBuilder();
        $builder->where('a.id = :id');
        $query = $builder->getQuery();
        $query->setHydrationMode($query::HYDRATE_OBJECT);
        return $query;
    }

    /**
     * @param $id
     * @return null|\Shopware\Models\Article\Article
     */
    public function getArticleModelById($id)
    {
        if($this->modelQuery === null) {
            $this->modelQuery = $this->getArticleModelByIdQuery();
        }
        $result = $this->modelQuery->execute(array('id' => $id));
        return isset($result[0]) ? $result[0] : null;
    }

    /**
     * @return \Doctrine\ORM\Query
     */
    private function getCategoryModelByIdQuery()
    {
        $repository = $this->getCategoryRepository();
        $builder = $repository->createQueryBuilder('c');
        $builder->join('c.attribute', 'ct');
        $builder->addSelect('ct');
        $builder->where('c.id = :id');
        $query = $builder->getQuery();
        $query->setHydrationMode($query::HYDRATE_OBJECT);
        return $query;
    }

    /**
     * @param $id
     * @return null|\Shopware\Models\Category\Category
     */
    public function getCategoryModelById($id)
    {
        if($this->categoryQuery === null) {
            $this->categoryQuery = $this->getCategoryModelByIdQuery();
        }
        $result = $this->categoryQuery->execute(array('id' => $id));
        return isset($result[0]) ? $result[0] : null;
    }

    /**
     * @param array $row
     * @return Product
     */
    public function getProductByRowData($row)
    {
        if(isset($row['deliveryDate'])) {
            $row['deliveryDate'] = $row['deliveryDate']->getTimestamp();
        }

        if(!empty($row['altDescription'])) {
            $row['longDescription'] = $row['altDescription'];
        }
        unset($row['altDescription']);

        // Fix categories
        if(is_string($row['categories'])) {
            $row['categories'] = unserialize($row['categories']);
        }

        // Fix prices
        foreach(array('price', 'purchasePrice', 'vat') as $name) {
            $row[$name] = round($row[$name], 2);
        }

        // Fix attributes
        foreach(array('weight', 'unit', 'base', 'volume') as $name) {
            if(isset($row[$name])) {
                $row['attributes'][$name] = $row[$name];
            }
            unset($row[$name]);
        }

        // Fix dimensions
        if(!empty($row['width']) && !empty($row['height'])) {
            $dimension = array(
                $row['width'], $row['height']
            );
            if(!empty($row['length'])) {
                $dimension[] = $row['length'];
            }
            $row['attributes'][Product::ATTRIBUTE_DIMENSION] = implode('x', $dimension);
        }
        unset($row['width'], $row['height'], $row['length']);

        // If the product is a remote product, the freeDelivery needs to be replaced
        // Else we need some price replacement
        if (!empty($row['shopId'])) {
            $row['freeDelivery'] = $row['bepadoFreeDelivery'];
        }else{
            if ($row['bepadoExportPrice']) {
                $row['price'] = $row['bepadoExportPrice'];
            }

            if ($row['bepadoExportPurchasePrice']) {
                $row['purchasePrice'] = $row['bepadoExportPurchasePrice'];
            }
        }
        unset($row['bepadoFreeDelivery']);
        unset($row['bepadoExportPrice']);
        unset($row['bepadoExportPurchasePrice']);


        $product = new Product(
            $row
        );
        return $product;
    }

    /**
     * @param Product $product
     * @param int $mode
     * @return null|ProductModel
     */
    public function getArticleModelByProduct(Product $product, $mode = Query::HYDRATE_OBJECT)
    {
        $builder = $this->getArticleModelQueryBuilder();
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
     * @param $id
     * @return null|array
     */
    public function getRowProductDataById($id)
    {
        if($this->productQuery === null) {
            $this->productQuery = $this->getProductQuery();
        }
        $result = $this->productQuery->execute(array('id' => $id));
        if(!isset($result[0])) {
            return null;
        }
        return $result[0];
    }

    /**
     * @param $id
     * @return \Bepado\SDK\Struct\Product
     */
    public function getProductById($id)
    {
        $data = $this->getRowProductDataById($id);
        $data['images'] = $this->getImagesById($id);
        return $this->getProductByRowData($data) ;
    }

    /**
     * Does the current basket contain bepado products?
     *
     * @param $session
     * @return bool
     */
    public function hasBasketBepadoProducts($session)
    {
        $connection = $this->manager->getConnection();
        $result = $connection->fetchArray(
            'SELECT ob.articleID

            FROM s_order_basket ob

            INNER JOIN s_articles_attributes aa
            ON aa.articleID = ob.articleID
            AND aa.bepado_shop_id IS NOT NULL

            WHERE sessionID=?
            LIMIT 1
            ',
            array($session)
        );

        return !empty($result);
    }

    /**
     * @param $id
     * @return string[]
     */
    public function getImagesById($id)
    {
        if($this->imagePath === null) {
            return array();
        }

        $builder = $this->manager->createQueryBuilder();
        $builder->select(array('i.path', 'i.extension', 'i.main', 'i.position'))
            ->from('Shopware\Models\Article\Image', 'i')
            ->where('i.articleId = :articleId')
            ->andWhere('i.parentId IS NULL')
            ->setParameter('articleId', $id)
            ->orderBy('i.main', 'ASC')
            ->addOrderBy('i.position', 'ASC');

        $query = $builder->getQuery();
        $query->setHydrationMode($query::HYDRATE_OBJECT);

        $images = $query->getArrayResult();

        $imagePath = $this->imagePath;
        $images = array_map(function($image) use ($imagePath) {
            return "{$imagePath}{$image['path']}.{$image['extension']}";
        }, $images);

        return $images;
    }

    /**
     * @param $id
     * @return null|array
     */
    public function getRowProductCategoriesById($id)
    {
        return $this->getCategoryQuery()->getRowProductCategoriesById($id);
    }

    /**
     * @param Product $product
     * @return \Shopware\Models\Category\Category[]
     */
    public function getCategoriesByProduct(Product $product)
    {
        return $this->getCategoryQuery()->getCategoriesByProduct($product);
    }

    protected function getCategoryQuery()
    {
        return $this->bepadoCategoryQuery;
    }

    /**
     * Helper to determine, if there is a main image for a given articleId
     *
     * @param $articleId
     * @return bool
     */
    public function hasArticleMainImage($articleId)
    {
        $builder = $this->manager->createQueryBuilder();
        $builder->select(array('images'))
            ->from('Shopware\Models\Article\Image', 'images')
            ->where('images.articleId = :articleId')
            ->andWhere('images.parentId IS NULL')
            ->andWhere('images.main = :main')
            ->setParameter('main', 1)
            ->setParameter('articleId', $articleId)
            ->setFirstResult(0)
            ->setMaxResults(1);

        $result = $builder->getQuery()->getResult();
        return !empty($result);
    }


    /**
     * Helper function to mark a given array of product ids for bepado update
     *
     * @param array $ids
     * @param $sdk SDK
     */
    public function insertOrUpdateProduct(array $ids, $sdk)
    {

        $errors = array();

        foreach($ids as $id) {
            $model = $this->getArticleModelById($id);
            if($model === null) {
                continue;
            }
            $attribute = $model->getAttribute();

            $status = $attribute->getBepadoExportStatus();
            if(empty($status) || $status == 'delete' || $status == 'error') {
                $status = 'insert';
            } else {
                $status = 'update';
            }
            $attribute->setBepadoExportStatus(
                $status
            );

            $categories = $this->getRowProductCategoriesById($id);
            $attribute->setBepadoCategories(
                serialize($categories)
            );

            Shopware()->Models()->flush($attribute);
            try {
                if($status == 'insert') {
                    $sdk->recordInsert($id);
                } else {
                    $sdk->recordUpdate($id);
                }
            } catch(Exception $e) {
                $attribute->setBepadoExportStatus(
                    'error'
                );
                $attribute->setBepadoExportMessage(
                    $e->getMessage() . "\n" . $e->getTraceAsString()
                );


                $prefix = $model && $model->getName() ? $model->getName() . ': ' : '';

                $errors[] = $prefix . $e->getMessage();
                Shopware()->Models()->flush($attribute);
            }
        }

        return $errors;
    }

}
