<?php

namespace Shopware\Bepado;

use Shopware\Components\Model\ModelManager;
use Doctrine\ORM\QueryBuilder;

/**
 * The ProductQuery will dynamically create a Query for a product depending on the current shopware configuration
 *
 * Class ProductQuery
 * @package Shopware\Bepado
 */
class ProductQuery
{

    /** @var \Shopware\Components\Model\ModelManager */
    protected $manager;

    /** @var  \Shopware\CustomModels\Bepado\ConfigRepository */
    private $configRepo;

    /** @var  string */
    private $productDescriptionField;

    public function __construct(ModelManager $manager, $productDescriptionField)
    {
        $this->manager = $manager;
        $this->productDescriptionField = $productDescriptionField;
    }

    /**
     * @return null|string
     */
    public function getProductDescriptionField()
    {
        return $this->productDescriptionField;
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
     * This method covers the fromShop products as well as the toShop products.
     *
     * As the fields might differ in some situations, the result of this query needs to be
     * postprocessed by the getProductByRowData method.
     *
     * Currently there are three field being "switched" in the getProductByRowData method:
     * - price          (configurable in fromShop, d.price in toShop)
     * - purchasePrice  (configurable in from Shop, d.basePrice in toShop)
     * - freeDelivery   (d.shippingFree in fromShop, at.freeDelivery in toShop)
     *
     * Two distinct queries?
     *
     * @return \Doctrine\ORM\Query
     */
    public function getProductQuery()
    {
        $builder = $this->manager->createQueryBuilder();

        $builder->from('Shopware\CustomModels\Bepado\Attribute', 'at');
        $builder->join('at.article', 'a');
        $builder->join('a.mainDetail', 'd');
        $builder->leftJoin('a.supplier', 's');
        $builder->leftJoin('d.prices', 'defaultPrice', 'with', "defaultPrice.from = 1 AND defaultPrice.customerGroupKey = 'EK'");
        $builder->join('a.tax', 't');
        $builder->join('d.attribute', 'attribute');
        $builder->leftJoin('d.unit', 'u');
        $builder->select(array(
            'at.shopId as shopId',
            'IFNULL(at.sourceId, a.id) as sourceId',
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
            at.freeDelivery as bepadoFreeDelivery',

            'd.releaseDate as deliveryDate',
            'd.inStock as availability',

            'd.width',
            'd.height',
            'd.len as length',

            'd.weight',
            'u.unit',
            'd.purchaseUnit as volume',
            'd.referenceUnit as base',
            'at.categories as categories',
            'at.fixedPrice as fixedPrice'
            //'images = array()',
        ));

        $this->addPriceExportSelect($builder);

        $builder->addSelect($this->productDescriptionField . ' as altDescription');
        $builder->where('a.id = :id');
        $query = $builder->getQuery();

        $query->setHydrationMode($query::HYDRATE_ARRAY);
        return $query;
    }


    /**
     * The fields from which the price and purchasePrice are going to be exported are configurable.
     * This helper method will add the corresponding selects/joins to the given builder object
     *
     * @param $builder
     * @return QueryBuilder
     */
    private function addPriceExportSelect(QueryBuilder $builder)
    {
        $repo = $this->getConfigRepository();
        $exportPriceCustomerGroup = $repo->getConfig('priceGroupForPriceExport', 'EK');
        $exportPurchasePriceCustomerGroup = $repo->getConfig('priceGroupForPurchasePriceExport', 'EK');
        $exportPriceColumn = $repo->getConfig('priceFieldForPriceExport', 'price');
        $exportPurchasePriceColumn = $repo->getConfig('priceFieldForPurchasePriceExport', 'basePrice');

        $builder->leftJoin(
            'd.prices',
            'exportPrice',
            'with',
            "exportPrice.from = 1 AND exportPrice.customerGroupKey = :priceCustomerGroup"
        );
        $builder->leftJoin(
            'd.prices',
            'exportPurchasePrice',
            'with',
            "exportPurchasePrice.from = 1 AND exportPurchasePrice.customerGroupKey = :purchasePriceCustomerGroup"
        );
        $builder->addSelect(
            array(
                "exportPrice.{$exportPriceColumn}  as bepadoExportPrice",
                "exportPurchasePrice.{$exportPurchasePriceColumn} as bepadoExportPurchasePrice"
            )
        );
        $builder->setParameter('priceCustomerGroup', $exportPriceCustomerGroup);
        $builder->setParameter('purchasePriceCustomerGroup', $exportPurchasePriceCustomerGroup);

        return $builder;
    }


}
