<?php

namespace ShopwarePlugins\Connect\Components\ProductQuery;

use Doctrine\ORM\QueryBuilder;
use Shopware\Connect\Struct\Product;
use ShopwarePlugins\Connect\Components\Exceptions\NoLocalProductException;
use ShopwarePlugins\Connect\Components\Logger;
use ShopwarePlugins\Connect\Components\Marketplace\MarketplaceGateway;
use ShopwarePlugins\Connect\Components\Translations\ProductTranslatorInterface;
use Shopware\Components\Model\ModelManager;
use ShopwarePlugins\Connect\Components\Config;
use ShopwarePlugins\Connect\Components\Utils\UnitMapper;
use Shopware\Connect\Struct\Translation;

/**
 * Will return a local product (e.g. for export) as Shopware\Connect\Struct\Product
 * Configured fields for price- and description export will be taken into account
 *
 * Class LocalProductQuery
 * @package ShopwarePlugins\Connect\Components\ProductQuery
 */
class LocalProductQuery extends BaseProductQuery
{
    protected $productDescriptionField;

    protected $baseProductUrl;

    /** @var \ShopwarePlugins\Connect\Components\Config $configComponent */
    protected $configComponent;

    protected $marketplaceGateway;

    /**
     * @var \ShopwarePlugins\Connect\Components\Translations\ProductTranslatorInterface
     */
    protected $productTranslator;

    public function __construct(
        ModelManager $manager,
        $productDescriptionField,
        $baseProductUrl,
        $configComponent,
        MarketplaceGateway $marketplaceGateway,
        ProductTranslatorInterface $productTranslator,
        $mediaService = null
    )
    {
        parent::__construct($manager, $mediaService);

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

        $builder->from('Shopware\CustomModels\Connect\Attribute', 'at');
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
            'd.number as sku',
            'at.shopId as shopId',
            'at.sourceId as sourceId',
            'd.kind as detailKind',
            'd.ean',
            'a.name as title',
            'a.description as shortDescription',
            's.name as vendorName',
            's.image as vendorImage',
            's.link as vendorLink',
            's.description as vendorDescription',
            's.metaTitle as vendorMetaTitle',
            't.tax / 100 as vat',

            'd.releaseDate as deliveryDate',
            'd.inStock as availability',
            'd.minPurchase as minPurchaseQuantity',

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
            $selectColumns[] = "exportPrice.{$exportPriceColumn} as price";
        }
        if ($exportPurchasePriceColumn && $exportPurchasePriceColumn == 'detailPurchasePrice') {
            $selectColumns[] = "d.purchasePrice as purchasePrice";
        } elseif ($exportPurchasePriceColumn) {
            $selectColumns[] = "exportPurchasePrice.{$exportPurchasePriceColumn} as purchasePrice";
        }

        $builder->select($selectColumns);

        $builder = $this->addMarketplaceAttributeSelect($builder, $articleAttributeAlias);
        $builder = $this->addPriceJoins($builder, $exportPriceColumn, $exportPurchasePriceColumn);

        $builder->setParameter('priceCustomerGroup', $exportPriceCustomerGroup);
        if ($exportPurchasePriceColumn != 'detailPurchasePrice') {
            $builder->setParameter('purchasePriceCustomerGroup', $exportPurchasePriceCustomerGroup);
        }

        return $builder;
    }

    /**
     * @param array $rows
     * @return array
     */
    public function getConnectProducts($rows)
    {
        $products = array();
        foreach ($rows as $row) {
            $products[] = $this->getConnectProduct($row);
        }
        return $products;
    }

    /**
     * @param $row
     * @return Product
     * @throws NoLocalProductException
     */
    public function getConnectProduct($row)
    {
        $row = $this->prepareCommonAttributes($row);
        $row['translations'] = $this->productTranslator->translate($row['localId'], $row['sourceId']);

        if (!empty($row['shopId'])) {
            throw new NoLocalProductException("Product {$row['title']} is not a local product");
        }

        $row['url'] = $this->getUrlForProduct($row['sourceId']);

        $row['images'] = $this->getImagesById($row['localId']);

        //todo@sb: find better way to collect configuration option translations
        $row = $this->applyConfiguratorOptions($row);
        $row = $this->prepareVendor($row);

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
            //Map local unit to connect unit
            if ($row['attributes']['unit']) {
                $unitMapper = new UnitMapper($this->configComponent, $this->manager);
                $row['attributes']['unit'] = $unitMapper->getConnectUnit($row['attributes']['unit']);
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
        if ($exportPriceColumn == 'connectPrice') {
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
        if ($exportPurchasePriceColumn == 'connectPrice') {
            $builder->leftJoin(
                'd.prices',
                'price_join_for_export_purchase_price',
                'with',
                "price_join_for_export_purchase_price.from = 1 AND price_join_for_export_purchase_price.customerGroupKey = :purchasePriceCustomerGroup"
            );
            $builder->leftJoin('price_join_for_export_purchase_price.attribute', 'exportPurchasePrice');
        } elseif ($exportPurchasePriceColumn != 'detailPurchasePrice') {
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
        $result = $this->manager->getConnection()->fetchColumn(
            'SELECT d.id FROM s_articles_details d WHERE d.kind = 2 AND articleID = ?',
            array((int)$productId)
        );

        return $result > 0;
    }

    /**
     * @param $row
     * @return array
     */
    private function prepareVendor($row)
    {
        $row['vendor'] = array(
            'name' => $row['vendorName'],
            'url' => $row['vendorLink'],
            'logo_url' => null,
            'description' => $row['vendorDescription'],
            'page_title' => $row['vendorMetaTitle'],
        );

        if($row['vendorImage']){
            $info = pathinfo($row['vendorImage']);
            $row['vendor']['logo_url'] = $this->getImagePath($info['basename']);
        }

        unset($row['vendorName']);
        unset($row['vendorLink']);
        unset($row['vendorImage']);
        unset($row['vendorDescription']);
        unset($row['vendorMetaTitle']);

        return $row;
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

