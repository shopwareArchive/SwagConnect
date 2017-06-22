<?php

namespace ShopwarePlugins\Connect\Components\ProductQuery;

use Doctrine\ORM\QueryBuilder;
use Shopware\Bundle\StoreFrontBundle\Service\ContextServiceInterface;
use Shopware\Bundle\StoreFrontBundle\Service\Core\ContextService;
use Shopware\Bundle\StoreFrontBundle\Struct\ListProduct;
use Shopware\Connect\Struct\Product;
use Shopware\Connect\Struct\Property;
use ShopwarePlugins\Connect\Components\Exceptions\NoLocalProductException;
use ShopwarePlugins\Connect\Components\Marketplace\MarketplaceGateway;
use ShopwarePlugins\Connect\Components\MediaService;
use ShopwarePlugins\Connect\Components\Translations\ProductTranslatorInterface;
use Shopware\Components\Model\ModelManager;
use ShopwarePlugins\Connect\Components\Utils\UnitMapper;
use Shopware\Connect\Struct\PriceRange;
use Enlight_Event_EventManager;

/**
 * Will return a local product (e.g. for export) as Shopware\Connect\Struct\Product
 * Configured fields for price- and description export will be taken into account
 *
 * Class LocalProductQuery
 * @package ShopwarePlugins\Connect\Components\ProductQuery
 */
class LocalProductQuery extends BaseProductQuery
{
    const IMAGE_LIMIT = 10;

    const VARIANT_IMAGE_LIMIT = 10;

    protected $baseProductUrl;

    /**
     * @var \ShopwarePlugins\Connect\Components\Config $configComponent
     */
    protected $configComponent;

    /**
     * @var MarketplaceGateway
     */
    protected $marketplaceGateway;

    /**
     * @var \ShopwarePlugins\Connect\Components\Translations\ProductTranslatorInterface
     */
    protected $productTranslator;

    /**
     * @var \Shopware\Bundle\StoreFrontBundle\Service\ContextServiceInterface
     */
    protected $contextService;

    /**
     * @var \Shopware\Bundle\StoreFrontBundle\Service\Core\MediaService
     */
    protected $localMediaService;

    /**
     * @var \Shopware\Bundle\StoreFrontBundle\Struct\ProductContext
     */
    protected $productContext;

    /**
     * @var Enlight_Event_EventManager
     */
    private $eventManager;

    /**
     * LocalProductQuery constructor.
     * @param ModelManager $manager
     * @param null $baseProductUrl
     * @param $configComponent
     * @param MarketplaceGateway $marketplaceGateway
     * @param ProductTranslatorInterface $productTranslator
     * @param ContextServiceInterface $contextService
     * @param MediaService $storeFrontMediaService
     * @param null $mediaService
     * @param Enlight_Event_EventManager $eventManager
     */
    public function __construct(
        ModelManager $manager,
        $baseProductUrl,
        $configComponent,
        MarketplaceGateway $marketplaceGateway,
        ProductTranslatorInterface $productTranslator,
        ContextServiceInterface $contextService,
        MediaService $storeFrontMediaService,
        Enlight_Event_EventManager $eventManager,
        $mediaService = null
    )
    {
        parent::__construct($manager, $mediaService);

        $this->baseProductUrl = $baseProductUrl;
        $this->configComponent = $configComponent;
        $this->marketplaceGateway = $marketplaceGateway;
        $this->productTranslator = $productTranslator;
        $this->contextService = $contextService;
        $this->eventManager = $eventManager;
        $this->localMediaService = $storeFrontMediaService;

        // products context is needed to load product media
        // it's used for image translations
        // in our case translations are not used
        // so we don't care about shop language
        $this->productContext = $this->contextService->createShopContext(
            $this->configComponent->getDefaultShopId(),
            null,
            ContextService::FALLBACK_CUSTOMER_GROUP
        );
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
        $builder->leftJoin('d.attribute', 'attribute');
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
            's.name as vendorName',
            's.image as vendorImage',
            's.link as vendorLink',
            's.description as vendorDescription',
            's.metaTitle as vendorMetaTitle',
            't.tax / 100 as vat',

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
            'd.shippingTime as deliveryWorkDays',
            'a.lastStock',
        );

        if ($this->configComponent->getConfig(self::SHORT_DESCRIPTION_FIELD, false)){
            $selectColumns[] = 'a.description as shortDescription';
        }

        if ($this->configComponent->getConfig(self::LONG_DESCRIPTION_FIELD, false)){
            $selectColumns[] = 'a.descriptionLong as longDescription';
        }

        if ($this->configComponent->getConfig(self::CONNECT_DESCRIPTION_FIELD, false)){
            $selectColumns[] = 'attribute.connectProductDescription as additionalDescription';
        }

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
		$row['priceRanges'] = $this->preparePriceRanges($row['detailId']);

        $row['properties'] = $this->prepareProperties($row['localId']);

        $product = new ListProduct($row['localId'], $row['detailId'], $row['sku']);

        $sku = $row['sku'];
        $row['images'] = array();
        $mediaFiles = $this->localMediaService->getProductMediaList([$product], $this->productContext);
        if (array_key_exists($sku, $mediaFiles) && $mediaFiles[$sku]) {
            $mediaFiles[$sku] = array_slice($mediaFiles[$sku], 0, self::IMAGE_LIMIT);
            foreach ($mediaFiles[$sku] as $media) {
                $row['images'][] = $media->getFile();
            }
        }

        $variantMediaFiles = $this->localMediaService->getVariantMediaList([$product], $this->productContext);
        if (array_key_exists($sku, $variantMediaFiles) && $variantMediaFiles[$sku]) {
            $variantMediaFiles[$sku] = array_slice($variantMediaFiles[$sku], 0, self::VARIANT_IMAGE_LIMIT);
            foreach ($variantMediaFiles[$sku] as $media) {
                $row['variantImages'][] = $media->getFile();
                $row['images'][] = $media->getFile();
            }
        }

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

        if ((array_key_exists('unit', $row['attributes']) && $row['attributes']['unit'])
            && (array_key_exists('quantity', $row['attributes']) && $row['attributes']['quantity'])
            && (array_key_exists('ref_quantity', $row['attributes']) && $row['attributes']['ref_quantity'])
        ) {
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

        $this->eventManager->notify(
            'Connect_Supplier_Get_Single_Product_Before',
            [
                'subject' => $this,
                'product' => $product
            ]
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
        foreach ($this->marketplaceGateway->getMappings() as $mapping) {
            if (strlen($mapping['shopwareAttributeKey']) > 0 && strlen($mapping['attributeKey']) > 0) {
                $builder->addSelect("{$alias}.{$mapping['shopwareAttributeKey']}");
            }
        }

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
            'SELECT a.configurator_set_id FROM s_articles a WHERE a.id = ?',
            array((int)$productId)
        );

        return $result > 0;
    }

    /**
     * @param $detailId
     * @return PriceRange[]
     */
    protected function preparePriceRanges($detailId)
    {
        $prices = $this->getPriceRanges($detailId);

        $priceRanges = array();
        foreach ($prices as $price) {
            $clonePrice = $price;

            if ($price['to'] == 'beliebig') {
                $clonePrice['to'] = PriceRange::ANY;
            } else {
                $clonePrice['to'] = (int) $price['to'];
            }

            $priceRanges[] = new PriceRange($clonePrice);
        }

        return $priceRanges;
    }

    /**
     * @param int $detailId
     * @return array
     */
    private function getPriceRanges($detailId)
    {
        $exportPriceCustomerGroup = $this->configComponent->getConfig('priceGroupForPriceExport', 'EK');
        $exportPriceColumn = $this->configComponent->getConfig('priceFieldForPriceExport');

        $columns = ['p.from', 'p.to', 'p.customerGroupKey'];

        if ($exportPriceColumn) {
            $columns[] = "p.{$exportPriceColumn} as price";
        }

        $builder = $this->manager->createQueryBuilder();
        $builder->select($columns)
            ->from('Shopware\Models\Article\Price', 'p')
            ->where('p.articleDetailsId = :detailId')
            ->andWhere('p.customerGroupKey = :groupKey')
            ->setParameter('detailId', $detailId)
            ->setParameter('groupKey', $exportPriceCustomerGroup);

        return $builder->getQuery()->getArrayResult();
    }

    /**
     * @param $articleId
     * @return Property[]
     */
    protected function prepareProperties($articleId)
    {
        $properties = $this->getProperties($articleId);
        $attrGroup = $this->attributeGroup($articleId);

        // if product property group exist then the
        // property values are still old by that
        // this will not generate wrong Connect changes
        $property = reset($properties);
        if ($attrGroup) {
            $groupName = $attrGroup->getName();
            $groupPosition = $attrGroup->getPosition();
        } else {
            $groupName = $property['groupName'];
            $groupPosition = $property['groupPosition'];
        }

        $propertyArray = array();
        foreach ($properties as $property) {
            $cloneProperty = $property;
            $cloneProperty['groupName'] = $groupName;
            $cloneProperty['groupPosition'] = $groupPosition;
            $propertyArray[] = new Property($cloneProperty);
        }

        return $propertyArray;
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

