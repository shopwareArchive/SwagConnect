<?php

namespace Shopware\Bepado\Components\ProductQuery;

use Doctrine\ORM\QueryBuilder;
use Bepado\SDK\Struct\Product;
use Shopware\Bepado\Components\Exceptions\NoLocalProductException;
use Shopware\Bepado\Components\Logger;
use Shopware\Bepado\Components\Marketplace\MarketplaceGateway;
use Shopware\Bepado\Components\Translations\ProductTranslatorInterface;
use Shopware\Components\Model\ModelManager;
use Shopware\Bepado\Components\Config;
use Shopware\Bepado\Components\Utils\UnitMapper;
use Bepado\SDK\Struct\Translation;

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

    protected $marketplaceGateway;

    /**
     * @var \Shopware\Bepado\Components\Translations\ProductTranslatorInterface
     */
    protected $productTranslator;

    public function __construct(
        ModelManager $manager,
        $productDescriptionField,
        $baseProductUrl,
        $configComponent,
        MarketplaceGateway $marketplaceGateway,
        ProductTranslatorInterface $productTranslator
    )
    {
        $this->manager = $manager;
        $this->productDescriptionField = $productDescriptionField;
        $this->baseProductUrl = $baseProductUrl;
        $this->configComponent = $configComponent;
        $this->marketplaceGateway = $marketplaceGateway;
        $this->productTranslator = $productTranslator;
    }

    /**
     * @return QueryBuilder
     */
    public function getProductQuery()
    {
        $articleAttributeAlias = 'attribute';
        $exportPriceCustomerGroup = $this->configComponent->getConfig('priceGroupForPriceExport', 'EK');
        $exportPurchasePriceCustomerGroup = $this->configComponent->getConfig('priceGroupForPurchasePriceExport', 'EK');
        $exportPriceColumn = $this->configComponent->getConfig('priceFieldForPriceExport');
        $exportPurchasePriceColumn = $this->configComponent->getConfig('priceFieldForPurchasePriceExport');

        $builder = $this->manager->createQueryBuilder();

        $builder->from('Shopware\CustomModels\Bepado\Attribute', 'at');
        $builder->join('at.article', 'a');
        $builder->join('at.articleDetail', 'd');
        $builder->leftJoin('a.supplier', 's');
        $builder->join('a.tax', 't');
        $builder->join('d.attribute', 'attribute');
        $builder->leftJoin('d.unit', 'u');
        $builder->where('at.shopId IS NULL');
        $selectColumns = array(
            'a.id as localId',
            'd.id as detailId',
            'at.shopId as shopId',
            'at.sourceId as sourceId',
            'd.kind as detailKind',
            'd.ean',
            'a.name as title',
            'a.description as shortDescription',
            's.name as vendor',
            't.tax / 100 as vat',

            'd.releaseDate as deliveryDate',
            'd.inStock as availability',

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
        );

        if ($exportPriceColumn) {
            $selectColumns[] = "exportPrice.{$exportPriceColumn}  as price";
        }
        if ($exportPurchasePriceColumn) {
            $selectColumns[] = "exportPurchasePrice.{$exportPurchasePriceColumn} as purchasePrice";
        }
        $builder->select($selectColumns);


        $builder = $this->addMarketplaceAttributeSelect($builder, $articleAttributeAlias);
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
     * @throws NoLocalProductException
     */
    public function getBepadoProduct($row)
    {
        $row = $this->prepareCommonAttributes($row);
        $row['translations'] = $this->productTranslator->translate($row['localId'], $row['sourceId']);

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

        $row = $this->applyConfiguratorOptions($row);

        if ($row['deliveryWorkDays']) {
            $row['deliveryWorkDays'] = (int)$row['deliveryWorkDays'];
        } else {
            $row['deliveryWorkDays'] = null;
        }

        if ($this->hasVariants($row['localId'])) {
            $row['groupId'] = $row['localId'];
        }

        unset($row['localId']);
        unset($row['detailId']);
        unset($row['detailKind']);

        if ($row['attributes']['unit'] && $row['attributes']['quantity'] && $row['attributes']['ref_quantity']) {
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

    public function getUrlForProduct($productId, $shopId = null)
    {
        $shopId = (int)$shopId;
        $url = $this->baseProductUrl . $productId;
        if ($shopId > 0) {
            $url = $url . '/shId/' . $shopId;
        }

        return $url;
    }

	/**
     * Select attributes  which are already mapped to marketplace
     *
     * @param QueryBuilder $builder
     * @param $alias
     * @return QueryBuilder
     */
    private function addMarketplaceAttributeSelect(QueryBuilder $builder, $alias)
    {
        array_walk($this->marketplaceGateway->getMappings(), function($mapping) use ($builder, $alias) {
            if (strlen($mapping['shopwareAttributeKey']) > 0 && strlen($mapping['attributeKey']) > 0) {
                $builder->addSelect("{$alias}.{$mapping['shopwareAttributeKey']}");
            }
        });

        return $builder;
    }

    /**
     * Returns shopware to martketplace attributes mapping as array
     *
     * @return array
     */
    public function getAttributeMapping()
    {
        $mappings = $this->marketplaceGateway->getMappings();

        return array_merge(
            array_filter(array_combine(array_map(function($mapping) {
                return $mapping['shopwareAttributeKey'];
            }, $mappings), array_map(function($mapping) {
                return $mapping['attributeKey'];
            }, $mappings)), function($mapping) {
                return strlen($mapping['shopwareAttributeKey']) > 0 && strlen($mapping['attributeKey']) > 0;
            }),
            $this->attributeMapping
        );
    }

	/**
     * Check whether the product contains variants
     *
     * @param int $productId
     * @return bool
     */
    public function hasVariants($productId)
    {
        $builder = $this->manager->createQueryBuilder();

        $builder->from('Shopware\Models\Article\Detail', 'd');
        $builder->select(array(
            'COUNT(d.id) as detailsCount'
        ));

        $builder->where("d.articleId = :productId");
        $builder->setParameter(':productId', $productId);
        $query = $builder->getQuery();

        return $query->getSingleScalarResult() > 1 ? true : false;
    }

    /**
     * Applies configurator options and groups
     * to article array
     *
     * @param array $row
     * @return array
     */
    private function applyConfiguratorOptions($row)
    {
        $builder = $this->manager->createQueryBuilder();
        $builder->from('Shopware\Models\Article\Detail', 'd');
        $builder->join('d.configuratorOptions', 'cor');
        $builder->join('cor.group', 'cg');
        $builder->select(array(
            'cor.name as optionName',
            'cor.id as optionId',
            'cg.name as groupName',
            'cg.id as groupId',
        ));
        $builder->where("d.id = :detailId");
        $builder->setParameter(':detailId', $row['detailId']);

        $query = $builder->getQuery();

        $configuratorData = array();
        $configs = $query->getArrayResult();

        foreach ($configs as $config) {
            $row['translations'] = $this->productTranslator->translateConfiguratorGroup($config['groupId'], $config['groupName'], $row['translations']);
            $row['translations'] = $this->productTranslator->translateConfiguratorOption($config['optionId'], $config['optionName'], $row['translations']);

            $groupName = $config['groupName'];
            $configuratorData[$groupName] = $config['optionName'];
        }

        $row['variant'] = $configuratorData;

        foreach ($row['translations'] as $key => $translation) {
            try {
                // todo@sb: test me
                $this->productTranslator->validate($translation, count($configs));
            } catch(\Exception $e) {
                unset($row['translations'][$key]);
            }
        }

        return $row;
    }
}

