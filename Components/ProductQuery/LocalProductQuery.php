<?php

namespace Shopware\Bepado\Components\ProductQuery;

use Doctrine\ORM\QueryBuilder;
use Bepado\SDK\Struct\Product;
use Shopware\Bepado\Components\Exceptions\NoLocalProductException;
use Shopware\Components\Model\ModelManager;

class LocalProductQuery extends BaseProductQuery
{

    protected $configRepo;

    protected $manager;

    protected $productDescriptionField;

    protected $baseProductUrl;

    public function __construct(ModelManager $manager, $productDescriptionField, $baseProductUrl)
    {
        $this->manager = $manager;
        $this->productDescriptionField = $productDescriptionField;
        $this->baseProductUrl = $baseProductUrl;
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
     * @return QueryBuilder
     */
    public function getProductQuery()
    {
        $repo = $this->getConfigRepository();
        $exportPriceCustomerGroup = $repo->getConfig('priceGroupForPriceExport', 'EK');
        $exportPurchasePriceCustomerGroup = $repo->getConfig('priceGroupForPurchasePriceExport', 'EK');
        $exportPriceColumn = $repo->getConfig('priceFieldForPriceExport', 'price');
        $exportPurchasePriceColumn = $repo->getConfig('priceFieldForPurchasePriceExport', 'basePrice');

        $builder = $this->manager->createQueryBuilder();

        $builder->from('Shopware\CustomModels\Bepado\Attribute', 'at');
        $builder->join('at.article', 'a');
        $builder->join('a.mainDetail', 'd');
        $builder->leftJoin('a.supplier', 's');
        $builder->join('a.tax', 't');
        $builder->join('d.attribute', 'attribute');
        $builder->leftJoin('d.unit', 'u');
        $builder->select(array(
            'a.id as localId',
            'at.shopId as shopId',
            'a.id as sourceId',
            'd.ean',
            'a.name as title',
            'a.description as shortDescription',
            's.name as vendor',
            't.tax / 100 as vat',
            'at.freeDelivery as freeDelivery',

            'd.releaseDate as deliveryDate',
            'd.inStock as availability',
            "exportPrice.{$exportPriceColumn}  as price",
            "exportPurchasePrice.{$exportPurchasePriceColumn} as purchasePrice",
            $this->productDescriptionField . ' as longDescription',

            'd.width',
            'd.height',
            'd.len as length',

            'd.weight',
            'u.unit',
            'd.purchaseUnit as purchaseUnit',
            'd.referenceUnit as referenceUnit',
            'at.category as category',
            'at.fixedPrice as fixedPrice'
        ));

        $builder = $this->addPriceJoins($builder, $exportPriceColumn, $exportPurchasePriceColumn);

        $builder->setParameter('priceCustomerGroup', $exportPriceCustomerGroup);
        $builder->setParameter('purchasePriceCustomerGroup', $exportPurchasePriceCustomerGroup);

        return $builder;
    }

    /**
     * @param array $rows
     * @return array
     */
    public function getBepadoProducts($rows)
    {
        $products = array();
        foreach ($rows as $row) {
            $products[] = $this->getBepadoProduct($row);
        }
        return $products;
    }

    /**
     * @param $row
     * @return Product
     */
    public function getBepadoProduct($row)
    {
        $row = $this->prepareCommonAttributes($row);

        if (!empty($row['shopId'])) {
            throw new NoLocalProductException("Product {$row['title']} is not a local product");
        }


        $row['url'] = $this->getUrlForProduct($row['sourceId']);

        $row['images'] = $this->getImagesById($row['localId']);
        unset($row['localId']);

        $product = new Product(
            $row
        );
        return $product;
    }

    /**
     * Will add the correct joins depending on the configuration of the price columns
     *
     * @param $builder QueryBuilder
     * @param $exportPriceColumn
     * @param $exportPurchasePriceColumn
     * @return QueryBuilder
     */
    public function addPriceJoins(QueryBuilder $builder, $exportPriceColumn, $exportPurchasePriceColumn)
    {
        // When the price attribute is used, we need two joins to get it
        if ($exportPriceColumn == 'bepadoPrice') {
            $builder->leftJoin('d.prices', 'price_join_for_export_price', 'with', "price_join_for_export_price.from = 1 AND price_join_for_export_price.customerGroupKey = :priceCustomerGroup");
            $builder->leftJoin('price_join_for_export_price.attribute', 'exportPrice');
        } else {
            $builder->leftJoin('d.prices', 'exportPrice', 'with', "exportPrice.from = 1 AND exportPrice.customerGroupKey = :priceCustomerGroup");
        }

        // When the price attribute is used, we need two joins to get it
        if ($exportPurchasePriceColumn == 'bepadoPrice') {
            $builder->leftJoin('d.prices', 'price_join_for_export_purchase_price', 'with', "price_join_for_export_purchase_price.from = 1 AND price_join_for_export_purchase_price.customerGroupKey = :purchasePriceCustomerGroup");
            $builder->leftJoin('price_join_for_export_purchase_price.attribute', 'exportPurchasePrice');
        } else {
            $builder->leftJoin('d.prices', 'exportPurchasePrice', 'with', "exportPurchasePrice.from = 1 AND exportPurchasePrice.customerGroupKey = :purchasePriceCustomerGroup");
        }


        return $builder;
    }

    public function getUrlForProduct($productId)
    {
        return $this->baseProductUrl  . $productId;
    }

}

