<?php

namespace Shopware\Bepado\Components\ProductQuery;

use Bepado\SDK\Struct\Product;
use Doctrine\ORM\QueryBuilder;
use Shopware\Components\Model\ModelManager;

/**
 * Will return an *imported* product as Bepado\SDK\Struct\Product
 *
 * Class RemoteProductQuery
 * @package Shopware\Bepado\Components\ProductQuery
 */
class RemoteProductQuery extends BaseProductQuery
{

    protected $manager;

    protected $productDescriptionField;

    public function __construct(ModelManager $manager, $productDescriptionField)
    {
        $this->manager = $manager;
        $this->productDescriptionField = $productDescriptionField;
    }

    /**
     *
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getProductQuery()
    {
        $builder = $this->manager->createQueryBuilder();

        $builder->from('Shopware\CustomModels\Bepado\Attribute', 'at');
        $builder->join('at.article', 'a');
        $builder->join('at.articleDetail', 'd');
        $builder->leftJoin('a.supplier', 's');
        $builder->leftJoin('d.prices', 'defaultPrice', 'with', "defaultPrice.from = 1 AND defaultPrice.customerGroupKey = 'EK'");
        $builder->join('a.tax', 't');
        $builder->join('d.attribute', 'attribute');
        $builder->leftJoin('d.unit', 'u');
        $builder->where('at.shopId IS NOT NULL');
        $builder->select(array(
            'a.id as localId',
            'at.shopId as shopId',
            'at.sourceId as sourceId',
            'at.purchasePriceHash as purchasePriceHash',
            'at.offerValidUntil as offerValidUntil',
            'd.ean',
            'a.name as title',
            'a.description as shortDescription',
            'a.descriptionLong as longDescription',
            's.name as vendor',
            't.tax / 100 as vat',
            'defaultPrice.price as price',
            'at.purchasePrice as purchasePrice',

            'd.releaseDate as deliveryDate',
            'd.inStock as availability',

            'd.width',
            'd.height',
            'd.len as length',

            'd.weight',
            'u.unit',
            'd.purchaseUnit as purchaseUnit',
            'd.referenceUnit as referenceUnit',
            'at.category as category',
            'at.fixedPrice as fixedPrice',
            'attribute.bepadoArticleShipping as shipping',
        ));

        return $builder;
    }

    /**
     * Returns a list of bepado products
     *
     * @param array $rows
     * @return array
     */
    public function getBepadoProducts($rows)
    {
        $products = array();
        foreach ($rows as $row) {
            $product = $this->getBepadoProduct($row);
            if ($product) {
                $products[] = $product;
            }
        }
        return $products;
    }

    /**
     * Returns a bepado product or null if the given row does not reference a imported product
     *
     * @param $row
     * @return Product|null
     */
    protected function getBepadoProduct($row)
    {
        $row = $this->prepareCommonAttributes($row);

        if (empty($row['shopId'])) {
            return null;
        }
        unset($row['localId']);

        $product = new Product(
            $row
        );
        return $product;
    }


}

