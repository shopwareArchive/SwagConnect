<?php

namespace Shopware\Bepado\Components\ProductQuery;

use Doctrine\ORM\QueryBuilder;
use Bepado\SDK\Struct\Product;
use Shopware\Bepado\Components\Exceptions\NoLocalProductException;
use Shopware\Bepado\Components\Logger;
use Shopware\Components\Model\ModelManager;
use Shopware\Bepado\Components\Config;
use Shopware\Bepado\Components\Utils\UnitMapper;

/**
 * Will return a local product (e.g. for export) as Bepado\SDK\Struct\Product
 * Configured fields for price- and description export will be taken into account
 *
 * Class LocalProductQuery
 * @package Shopware\Bepado\Components\ProductQuery
 */
class LocalProductQuery extends BaseProductQuery
{

    protected $manager;

    protected $productDescriptionField;

    protected $baseProductUrl;

    /** @var \Shopware\Bepado\Components\Config $configComponent */
    protected $configComponent;

    public function __construct(ModelManager $manager, $productDescriptionField, $baseProductUrl, $configComponent)
    {
        $this->manager = $manager;
        $this->productDescriptionField = $productDescriptionField;
        $this->baseProductUrl = $baseProductUrl;
        $this->configComponent = $configComponent;
    }

    /**
     * @return QueryBuilder
     */
    public function getProductQuery()
    {

        $exportPriceCustomerGroup = $this->configComponent->getConfig('priceGroupForPriceExport', 'EK');
        $exportPurchasePriceCustomerGroup = $this->configComponent->getConfig('priceGroupForPurchasePriceExport', 'EK');
        $exportPriceColumn = $this->configComponent->getConfig('priceFieldForPriceExport', 'price');
        $exportPurchasePriceColumn = $this->configComponent->getConfig('priceFieldForPurchasePriceExport', 'basePrice');

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
            'at.fixedPrice as fixedPrice',
            'd.shippingTime as deliveryWorkDays',
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

        // add default export category
        if (empty($row['categories'])) {
            $defaultExportCategory = $this->configComponent->getConfig('defaultExportCategory');
            if ($defaultExportCategory) {
                $row['categories'][] = $this->configComponent->getConfig('defaultExportCategory');
            }
        }

        $row['url'] = $this->getUrlForProduct($row['sourceId']);

        $row['images'] = $this->getImagesById($row['localId']);

        if ($row['deliveryWorkDays']) {
            $row['deliveryWorkDays'] = (int)$row['deliveryWorkDays'];
        } else {
            $row['deliveryWorkDays'] = null;
        }

        unset($row['localId']);

        if ($row['attributes']['unit'] && $row['attributes']['quantity'] && $row['attributes']['ref_quantity'])
        {
            //Map local unit to bepado unit
            if ($row['attributes']['unit']) {
                $unitMapper = new UnitMapper($this->configComponent, $this->manager);
                $row['attributes']['unit'] = $unitMapper->getBepadoUnit($row['attributes']['unit']);
            }

            $intRefQuantity = (int)$row['attributes']['ref_quantity'];
            if ($row['attributes']['ref_quantity'] - $intRefQuantity <= 0.0001) {
                $row['attributes']['ref_quantity'] = $intRefQuantity;
            }
        } else {
            unset($row['attributes']['unit']);
            $row['attributes']['quantity'] = null;
            $row['attributes']['ref_quantity'] = null;
        }

        $product = new Product($row);
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
            $builder->leftJoin(
                'd.prices',
                'price_join_for_export_price',
                'with',
                "price_join_for_export_price.from = 1 AND price_join_for_export_price.customerGroupKey = :priceCustomerGroup"
            );
            $builder->leftJoin('price_join_for_export_price.attribute', 'exportPrice');
        } else {
            $builder->leftJoin(
                'd.prices',
                'exportPrice',
                'with',
                "exportPrice.from = 1 AND exportPrice.customerGroupKey = :priceCustomerGroup"
            );
        }

        // When the price attribute is used, we need two joins to get it
        if ($exportPurchasePriceColumn == 'bepadoPrice') {
            $builder->leftJoin(
                'd.prices',
                'price_join_for_export_purchase_price',
                'with',
                "price_join_for_export_purchase_price.from = 1 AND price_join_for_export_purchase_price.customerGroupKey = :purchasePriceCustomerGroup"
            );
            $builder->leftJoin('price_join_for_export_purchase_price.attribute', 'exportPurchasePrice');
        } else {
            $builder->leftJoin(
                'd.prices',
                'exportPurchasePrice',
                'with',
                "exportPurchasePrice.from = 1 AND exportPurchasePrice.customerGroupKey = :purchasePriceCustomerGroup"
            );
        }

        return $builder;
    }

    public function getUrlForProduct($productId)
    {
        return $this->baseProductUrl . $productId;
    }
}

