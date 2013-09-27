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
     * @return \Doctrine\ORM\Query
     */
    private function getProductQuery()
    {
        $repository = $this->getArticleRepository();
        $builder = $repository->createQueryBuilder('a');
        $builder->join('a.mainDetail', 'd');
        $builder->join('d.attribute', 'at');
        $builder->leftJoin('a.supplier', 's');
        $builder->leftJoin('d.prices', 'p', 'with', "p.from = 1 AND p.customerGroupKey = 'EK'");
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
            'p.price * (100 + t.tax) / 100 as price',
            'p.basePrice * (100 + t.tax) / 100 as purchasePrice',
            //'"EUR" as currency',
            'd.shippingFree as freeDelivery',
            'd.releaseDate as deliveryDate',
            'd.inStock as availability',

            'd.width',
            'd.height',
            'd.len as length',

            'd.weight',
            'u.unit',
            'd.purchaseUnit as volume',
            'd.referenceUnit as base',
            'at.bepadoCategories as categories'
            //'images = array()',
        ));
        if($this->productDescriptionField !== null) {
            $builder->addSelect('at.' . $this->productDescriptionField . ' as altDescription');
        }
        $builder->where('a.id = :id');
        $query = $builder->getQuery();
        $query->setHydrationMode($query::HYDRATE_ARRAY);
        return $query;
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
        return $this->getProductByRowData($data);
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
}
