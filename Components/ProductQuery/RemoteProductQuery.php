<?php

namespace ShopwarePlugins\Connect\Components\ProductQuery;

use Shopware\Connect\Struct\Product;
use Doctrine\ORM\QueryBuilder;
use Shopware\Components\Model\ModelManager;

/**
 * Will return an *imported* product as Shopware\Connect\Struct\Product
 *
 * Class RemoteProductQuery
 * @package ShopwarePlugins\Connect\Components\ProductQuery
 */
class RemoteProductQuery extends BaseProductQuery
{
    protected $productDescriptionFields;

    public function __construct(ModelManager $manager, $productDescriptionFields)
    {
        parent::__construct($manager);

        $this->productDescriptionFields = $productDescriptionFields;
    }

    /**
     *
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getProductQuery()
    {
        $builder = $this->manager->createQueryBuilder();

        $builder->from('Shopware\CustomModels\Connect\Attribute', 'at');
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
            'd.minPurchase as minPurchaseQuantity',

            'd.width',
            'd.height',
            'd.len as length',

            'd.weight',
            'u.unit',
            'd.purchaseUnit as purchaseUnit',
            'd.referenceUnit as referenceUnit',
            'at.category as category',
            'at.fixedPrice as fixedPrice',
            'attribute.connectArticleShipping as shipping',
            'attribute.connectProductDescription as additionalDescription',
        ));

        return $builder;
    }

    /**
     * Returns a list of connect products
     *
     * @param array $rows
     * @return array
     */
    public function getConnectProducts($rows)
    {
        $products = array();
        foreach ($rows as $row) {
            $product = $this->getConnectProduct($row);
            if ($product) {
                $products[] = $product;
            }
        }
        return $products;
    }

    /**
     * Returns a connect product or null if the given row does not reference a imported product
     *
     * @param $row
     * @return Product|null
     */
    protected function getConnectProduct($row)
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

