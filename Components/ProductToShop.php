<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Components;

use Shopware\Bundle\SearchBundle\Sorting\ReleaseDateSorting;
use Shopware\Connect\Gateway;
use Shopware\Components\Model\CategoryDenormalization;
use Shopware\Connect\ProductToShop as ProductToShopBase;
use Shopware\Connect\Struct\OrderStatus;
use Shopware\Connect\Struct\Product;
use Shopware\Models\Article\Article as ProductModel;
use Shopware\Models\Article\Configurator\Option;
use Shopware\Models\Order\Status;
use Shopware\Models\Article\Detail as DetailModel;
use Shopware\Models\Attribute\Article as AttributeModel;
use Shopware\Components\Model\ModelManager;
use Shopware\Connect\Struct\PriceRange;
use Shopware\Connect\Struct\ProductUpdate;
use Shopware\CustomModels\Connect\ProductStreamAttribute;
use Shopware\Models\Customer\Group;
use Shopware\Connect\Struct\Property;
use Shopware\Models\ProductStream\ProductStream;
use Shopware\Models\Property\Group as PropertyGroup;
use Shopware\Models\Property\Option as PropertyOption;
use Shopware\Models\Property\Value as PropertyValue;
use ShopwarePlugins\Connect\Components\ProductStream\ProductStreamRepository;
use ShopwarePlugins\Connect\Components\ProductStream\ProductStreamService;
use ShopwarePlugins\Connect\Components\Translations\LocaleMapper;
use ShopwarePlugins\Connect\Components\Gateway\ProductTranslationsGateway;
use ShopwarePlugins\Connect\Components\Marketplace\MarketplaceGateway;
use ShopwarePlugins\Connect\Components\Utils\UnitMapper;
use Shopware\CustomModels\Connect\Attribute as ConnectAttribute;
use Shopware\Models\Article\Supplier;
use Shopware\Models\Tax\Tax;
use Shopware\Models\Article\Configurator\Set;

/**
 * The interface for products imported *from* connect *to* the local shop
 *
 * @category  Shopware
 * @package   Shopware\Plugins\SwagConnect
 */
class ProductToShop implements ProductToShopBase
{
    const RELATION_TYPE_RELATED = 'relationships';
    const RELATION_TYPE_SIMILAR = 'similar';

    /**
     * @var Helper
     */
    private $helper;

    /**
     * @var ModelManager
     */
    private $manager;

    /**
     * @var \ShopwarePlugins\Connect\Components\Config
     */
    private $config;

    /**
     * @var ImageImport
     */
    private $imageImport;

    /**
     * @var \ShopwarePlugins\Connect\Components\VariantConfigurator
     */
    private $variantConfigurator;

    /**
     * @var MarketplaceGateway
     */
    private $marketplaceGateway;

    /**
     * @var ProductTranslationsGateway
     */
    private $productTranslationsGateway;

    /**
     * @var \Shopware\Models\Shop\Repository
     */
    private $shopRepository;

    private $localeRepository;

    /**
     * @var CategoryResolver
     */
    private $categoryResolver;

    /**
     * @var \Shopware\Connect\Gateway
     */
    private $connectGateway;

    /**
     * @var \Enlight_Event_EventManager
     */
    private $eventManager;

    /**
     * @var CategoryDenormalization
     */
    private $categoryDenormalization;

    /**
     * @param Helper $helper
     * @param ModelManager $manager
     * @param ImageImport $imageImport
     * @param \ShopwarePlugins\Connect\Components\Config $config
     * @param VariantConfigurator $variantConfigurator
     * @param \ShopwarePlugins\Connect\Components\Marketplace\MarketplaceGateway $marketplaceGateway
     * @param ProductTranslationsGateway $productTranslationsGateway
     * @param CategoryResolver $categoryResolver
     * @param Gateway $connectGateway
     * @param \Enlight_Event_EventManager $eventManager
     * @param CategoryDenormalization $categoryDenormalization
     */
    public function __construct(
        Helper $helper,
        ModelManager $manager,
        ImageImport $imageImport,
        Config $config,
        VariantConfigurator $variantConfigurator,
        MarketplaceGateway $marketplaceGateway,
        ProductTranslationsGateway $productTranslationsGateway,
        CategoryResolver $categoryResolver,
        Gateway $connectGateway,
        \Enlight_Event_EventManager $eventManager,
        CategoryDenormalization $categoryDenormalization
    ) {
        $this->helper = $helper;
        $this->manager = $manager;
        $this->config = $config;
        $this->imageImport = $imageImport;
        $this->variantConfigurator = $variantConfigurator;
        $this->marketplaceGateway = $marketplaceGateway;
        $this->productTranslationsGateway = $productTranslationsGateway;
        $this->categoryResolver = $categoryResolver;
        $this->connectGateway = $connectGateway;
        $this->eventManager = $eventManager;
        $this->categoryDenormalization = $categoryDenormalization;
    }

    /**
     * Start transaction
     *
     * Starts a transaction, which includes all insertOrUpdate and delete
     * operations, as well as the revision updates.
     *
     * @return void
     */
    public function startTransaction()
    {
        $this->manager->getConnection()->beginTransaction();
    }

    /**
     * Commit transaction
     *
     * Commits the transactions, once all operations are queued.
     *
     * @return void
     */
    public function commit()
    {
        $this->manager->getConnection()->commit();
    }

    /**
     * Import or update given product
     *
     * Store product in your shop database as an external product. The
     * associated sourceId
     *
     * @param Product $product
     */
    public function insertOrUpdate(Product $product)
    {
        /** @var Product $product */
        $product = $this->eventManager->filter(
            'Connect_ProductToShop_InsertOrUpdate_Before',
            $product
        );

        // todo@dn: Set dummy values and make product inactive
        if (empty($product->title) || empty($product->vendor)) {
            return;
        }

        $number = $this->generateSKU($product);

        $detail = $this->helper->getArticleDetailModelByProduct($product);
        $detail = $this->eventManager->filter(
            'Connect_Merchant_Get_Article_Detail_After',
            $detail,
            [
                'product' => $product,
                'subject' => $this
            ]
        );

        $isMainVariant = false;
        if ($detail === null) {
            $active = $this->config->getConfig('activateProductsAutomatically', false) ? true : false;

            $model = $this->getSWProductModel($product, $active, $isMainVariant);

            $detail = $this->generateNewDetail($product, $model);
        } else {
            /** @var ProductModel $model */
            $model = $detail->getArticle();
            // fix for isMainVariant flag
            // in connect attribute table
            $mainDetail = $model->getMainDetail();
            $isMainVariant = $this->checkIfMainVariant($detail, $mainDetail);
            $this->variantConfigurator->configureVariantAttributes($product, $detail);
            $this->updateConfiguratorSetTypeFromProduct($model, $product);

            $this->cleanUpConfiguratorSet($model, $product);
        }

        $detail->setNumber($number);

        $detailAttribute = $this->getOrCreateAttributeModel($detail, $model);

        $connectAttribute = $this->helper->getConnectAttributeByModel($detail) ?: new ConnectAttribute;
        // configure main variant and groupId
        if ($isMainVariant === true) {
            $connectAttribute->setIsMainVariant(true);
        }
        $connectAttribute->setGroupId($product->groupId);

        list($updateFields, $flag) = $this->getUpdateFields($model, $detail, $connectAttribute, $product);
        $this->setPropertiesForNewProducts($updateFields, $model, $detailAttribute, $product);

        $this->saveVat($product, $model);

        $this->applyProductProperties($model, $product);

        $detailAttribute = $this->applyMarketplaceAttributes($detailAttribute, $product);

        $this->setConnectAttributesFromProduct($connectAttribute, $product);

        // store product categories to connect attribute
        $connectAttribute->setCategory($product->categories);

        $connectAttribute->setLastUpdateFlag($flag);

        $connectAttribute->setPurchasePriceHash($product->purchasePriceHash);
        $connectAttribute->setOfferValidUntil($product->offerValidUntil);

        $this->updateDetailFromProduct($detail, $product);

        // some shops have feature "sell not in stock",
        // then end customer should be able to by the product with stock = 0
        $shopConfiguration = $this->connectGateway->getShopConfiguration($product->shopId);
        if ($shopConfiguration && $shopConfiguration->sellNotInStock) {
            $model->setLastStock(false);
        } else {
            $model->setLastStock(true);
        }

        $this->detailSetUnit($detail, $product, $detailAttribute);

        $this->detailSetAttributes($detail, $product);

        $this->connectAttributeSetLastUpdate($connectAttribute, $product);

        if ($model->getMainDetail() === null) {
            $model->setMainDetail($detail);
        }

        if ($detail->getAttribute() === null) {
            $detail->setAttribute($detailAttribute);
            $detailAttribute->setArticle($model);
        }

        $connectAttribute->setArticle($model);
        $connectAttribute->setArticleDetail($detail);

        $this->eventManager->notify(
            'Connect_Merchant_Saving_ArticleAttribute_Before',
            [
                'subject' => $this,
                'connectAttribute' => $connectAttribute
            ]
        );

        //article has to be flushed
        $this->manager->persist($model);
        $this->manager->persist($connectAttribute);
        $this->manager->persist($detail);
        $this->manager->flush();

        $this->categoryResolver->storeRemoteCategories($product->categories, $model->getId(), $product->shopId);
        $categories = $this->categoryResolver->resolve($product->categories, $product->shopId, $product->stream);
        if (count($categories) > 0) {
            $detailAttribute->setConnectMappedCategory(true);
        }

        $this->manager->persist($detailAttribute);
        $this->manager->flush();

        $this->categoryDenormalization($model, $categories);

        $defaultCustomerGroup = $this->helper->getDefaultCustomerGroup();
        // Only set prices, if fixedPrice is active or price updates are configured
        if (count($detail->getPrices()) == 0 || $connectAttribute->getFixedPrice() || $updateFields['price']) {
            $this->setPrice($model, $detail, $product);
        }
        // If the price is not being update, update the purchasePrice anyway
        $this->setPurchasePrice($detail, $product->purchasePrice, $defaultCustomerGroup);

        $this->manager->clear();

        $this->addArticleTranslations($model, $product);

        if ($isMainVariant || $product->groupId === null) {
            $this->applyCrossSelling($model->getId(), $product);
        }

        //clear cache for that article
        $this->helper->clearArticleCache($model->getId());

        if ($updateFields['image']) {
            // Reload the model in order to not to work an the already flushed model
            $model = $this->helper->getArticleModelByProduct($product);
            // import only global images for article
            $this->imageImport->importImagesForArticle(array_diff($product->images, $product->variantImages), $model);
            if ($updateFields['mainImage'] && isset($product->images[0])) {
                $this->imageImport->importMainImage($product->images[0], $model->getId());
            }
            // Reload the article detail model in order to not to work an the already flushed model
            $detail = $this->helper->getArticleDetailModelByProduct($product);
            // import only specific images for variant
            $this->imageImport->importImagesForDetail($product->variantImages, $detail);
        }

        $this->eventManager->notify(
            'Connect_ProductToShop_InsertOrUpdate_After',
            [
                'connectProduct' => $product,
                'shopArticleDetail' => $detail
            ]
        );

        $stream = $this->getOrCreateStream($product);
        $this->addProductToStream($stream, $model);
    }

    /**
     * @param Product $product
     * @return string
     */
    private function generateSKU(Product $product)
    {
        if (!empty($product->sku)) {
            $number = 'SC-' . $product->shopId . '-' . $product->sku;
            $duplicatedDetail = $this->helper->getDetailByNumber($number);
            if ($duplicatedDetail
                && $this->helper->getConnectAttributeByModel($duplicatedDetail)->getSourceId() != $product->sourceId
            ) {
                $this->deleteDetail($duplicatedDetail);
            }
        } else {
            $number = 'SC-' . $product->shopId . '-' . $product->sourceId;
        }

        return $number;
    }

    /**
     * @param DetailModel $detailModel
     */
    private function deleteDetail(DetailModel $detailModel)
    {
        $this->eventManager->notify(
            'Connect_Merchant_Delete_Product_Before',
            [
                'subject' => $this,
                'articleDetail' => $detailModel
            ]
        );

        $article = $detailModel->getArticle();

        $this->removeOptions($detailModel, $article);

        // Not sure why, but the Attribute can be NULL
        $attribute = $this->helper->getConnectAttributeByModel($detailModel);
        $this->manager->remove($detailModel);

        if ($attribute) {
            $this->manager->remove($attribute);
        }

        // if removed variant is main variant
        // find first variant which is not main and mark it
        if ($detailModel->getKind() === 1) {
            /** @var \Shopware\Models\Article\Detail $variant */
            foreach ($article->getDetails() as $variant) {
                if ($variant->getId() != $detailModel->getId()) {
                    $variant->setKind(1);
                    $article->setMainDetail($variant);
                    $connectAttribute = $this->helper->getConnectAttributeByModel($variant);
                    if (!$connectAttribute) {
                        continue;
                    }
                    $connectAttribute->setIsMainVariant(true);
                    $this->manager->persist($connectAttribute);
                    $this->manager->persist($article);
                    $this->manager->persist($variant);
                    break;
                }
            }
        }

        if (count($details = $article->getDetails()) === 1) {
            $details->clear();
            $this->manager->remove($article);
        }

        //save category Ids before flush
        $oldCategoryIds = array_map(function ($category) {
            return $category->getId();
        }, $article->getCategories()->toArray());

        // Do not remove flush. It's needed when remove article,
        // because duplication of ordernumber. Even with remove before
        // persist calls mysql throws exception "Duplicate entry"
        $this->manager->flush();
        // always clear entity manager, because $article->getDetails() returns
        // more than 1 detail, but all of them were removed except main one.
        $this->manager->clear();

        // call this after flush because article has to be deleted that this works
        if (count($oldCategoryIds) > 0) {
            $this->categoryResolver->deleteEmptyConnectCategories($oldCategoryIds);
        }
    }

    /**
     * @param DetailModel $detailModel
     * @param $article
     */
    private function removeOptions(DetailModel $detailModel, $article)
    {
        $configSet = $article->getConfiguratorSet();
        $options = $detailModel->getConfiguratorOptions();
        if($options && $configSet) {
            $configSet->setOptions($options);
            $this->manager->persist($configSet);
        }
    }

    /**
     * @param Product $product
     * @param $active
     * @param $isMainVariant
     * @return null|Article
     */
    private function getSWProductModel(Product $product, $active, &$isMainVariant)
    {
        if ($product->groupId !== null) {
            $model = $this->helper->getArticleByRemoteProduct($product);
            if (!$model instanceof \Shopware\Models\Article\Article) {
                $model = $this->helper->createProductModel($product);
                $model->setActive($active);
                $isMainVariant = true;
            }
        } else {
            $model = $this->helper->getConnectArticleModel($product->sourceId, $product->shopId);
            if (!$model instanceof \Shopware\Models\Article\Article) {
                $model = $this->helper->createProductModel($product);
                $model->setActive($active);
            }
        }

        return $model;
    }

    /**
     * @param Product $product
     * @param $model
     * @return DetailModel
     */
    private function generateNewDetail(Product $product, $model)
    {
        $detail = new DetailModel();
        $detail->setActive($model->getActive());
        // added for 5.4 compatibility
        if (method_exists($detail, 'setLastStock')) {
            $shopConfiguration = $this->connectGateway->getShopConfiguration($product->shopId);
            if ($shopConfiguration && $shopConfiguration->sellNotInStock) {
                $detail->setLastStock(false);
            } else {
                $detail->setLastStock(true);
            }
        }
        $this->manager->persist($detail);
        $detail->setArticle($model);
        $model->getDetails()->add($detail);
        $this->variantConfigurator->configureVariantAttributes($product, $detail);

        return $detail;
    }

    /**
     * @param DetailModel $detail
     * @param DetailModel $mainDetail
     * @return bool
     */
    private function checkIfMainVariant(DetailModel $detail, DetailModel $mainDetail)
    {
        return $detail->getId() === $mainDetail->getId();
    }

    /**
     * @param ProductModel $model
     * @param Product $product
     */
    private function updateConfiguratorSetTypeFromProduct(ProductModel $model, Product $product)
    {
        $configSet = $model->getConfiguratorSet();
        if (!empty($product->variant) && $configSet instanceof Set) {
            $configSet->setType($product->configuratorSetType);
        }
    }

    /**
     * @param ProductModel $model
     * @param Product $product
     */
    private function cleanUpConfiguratorSet(ProductModel $model, Product $product)
    {
        if (empty($product->variant) && $model->getConfiguratorSet()) {
            $this->manager->getConnection()->executeQuery(
                'UPDATE s_articles SET configurator_set_id = NULL WHERE id = ?',
                [$model->getId()]
            );
        }
    }

    /**
     * @param DetailModel $detail
     * @param ProductModel $model
     * @return AttributeModel
     */
    private function getOrCreateAttributeModel(DetailModel $detail, ProductModel $model)
    {
        $detailAttribute = $detail->getAttribute();
        if (!$detailAttribute) {
            $detailAttribute = new AttributeModel();
            $detail->setAttribute($detailAttribute);
            $model->setAttribute($detailAttribute);
            $detailAttribute->setArticle($model);
            $detailAttribute->setArticleDetail($detail);
        }

        return $detailAttribute;
    }

    /**
     * Get array of update info for the known fields
     *
     * @param $model
     * @param $detail
     * @param $attribute
     * @param $product
     * @return array
     */
    public function getUpdateFields($model, $detail, $attribute, $product)
    {
        // This also defines the flags of these fields
        $fields = $this->helper->getUpdateFlags();
        $flagsByName = array_flip($fields);

        $flag = 0;
        $output = [];
        foreach ($fields as $key => $field) {
            // Don't handle the imageInitialImport flag
            if ($field == 'imageInitialImport') {
                continue;
            }

            if ($field == 'image' && !$this->config->getConfig(
                'importImagesOnFirstImport',
                    false
            )) {
                $output[$field] = false;
                $flag |= $flagsByName['imageInitialImport'];
                continue;
            }

            $updateAllowed = $this->isFieldUpdateAllowed($field, $model, $attribute);
            $output[$field] = $updateAllowed;
            if (!$updateAllowed && $this->hasFieldChanged($field, $model, $detail, $product)) {
                $flag |= $key;
            }
        }

        return [$output, $flag];
    }

    /**
     * Helper method to determine if a given $fields may/must be updated.
     * This method will check for the model->id in order to determine, if it is a new entity. Therefore
     * this method cannot be used after the model in question was already flushed.
     *
     * @param $field
     * @param $model ProductModel
     * @param $attribute ConnectAttribute
     * @throws \RuntimeException
     * @return bool|null
     */
    public function isFieldUpdateAllowed($field, ProductModel $model, ConnectAttribute $attribute)
    {
        $allowed = [
            'ShortDescription',
            'LongDescription',
            'AdditionalDescription',
            'Image',
            'Price',
            'Name',
            'MainImage',
        ];

        // Always allow updates for new models
        if (!$model->getId()) {
            return true;
        }

        $field = ucfirst($field);
        $attributeGetter = 'getUpdate' . $field;
        $configName = 'overwriteProduct' . $field;

        if (!in_array($field, $allowed)) {
            throw new \RuntimeException("Unknown update field {$field}");
        }

        $attributeValue = $attribute->$attributeGetter();


        // If the value is 'null' or 'inherit', the behaviour will be inherited from the global configuration
        // Once we have a supplier based configuration, we need to take it into account here
        if ($attributeValue == null || $attributeValue == 'inherit') {
            return $this->config->getConfig($configName, true);
        }

        return $attributeValue == 'overwrite';
    }

    /**
     * Determine if a given field has changed
     *
     * @param $field
     * @param ProductModel $model
     * @param DetailModel $detail
     * @param Product $product
     * @return bool
     */
    public function hasFieldChanged($field, ProductModel $model, DetailModel $detail, Product $product)
    {
        switch ($field) {
            case 'shortDescription':
                return $model->getDescription() != $product->shortDescription;
            case 'longDescription':
                return $model->getDescriptionLong() != $product->longDescription;
            case 'additionalDescription':
                return $detail->getAttribute()->getConnectProductDescription() != $product->additionalDescription;
            case 'name':
                return $model->getName() != $product->title;
            case 'image':
                return count($model->getImages()) != count($product->images);
            case 'mainImage':
                if ($product->images[0]) {
                    return $this->imageImport->hasMainImageChanged($product->images[0], $model->getId());
                }

                return false;
            case 'price':
                $prices = $detail->getPrices();
                if (empty($prices)) {
                    return true;
                }
                $price = $prices->first();
                if (!$price) {
                    return true;
                }

                return $prices->first()->getPrice() != $product->price;
        }

        throw new \InvalidArgumentException('Unrecognized field');
    }

    /**
     * @param array $updateFields
     * @param ProductModel $model
     * @param AttributeModel $detailAttribute
     * @param Product $product
     */
    private function setPropertiesForNewProducts(array $updateFields, ProductModel $model, AttributeModel $detailAttribute, Product $product)
    {
        /*
         * Make sure, that the following properties are set for
         * - new products
         * - products that have been configured to receive these updates
         */
        if ($updateFields['name']) {
            $model->setName($product->title);
        }
        if ($updateFields['shortDescription']) {
            $model->setDescription($product->shortDescription);
        }
        if ($updateFields['longDescription']) {
            $model->setDescriptionLong($product->longDescription);
        }

        if ($updateFields['additionalDescription']) {
            $detailAttribute->setConnectProductDescription($product->additionalDescription);
        }

        if ($product->vat !== null) {
            $repo = $this->manager->getRepository('Shopware\Models\Tax\Tax');
            $tax = round($product->vat * 100, 2);
            /** @var \Shopware\Models\Tax\Tax $tax */
            $tax = $repo->findOneBy(['tax' => $tax]);
            $model->setTax($tax);
        }

        if ($product->vendor !== null) {
            $repo = $this->manager->getRepository('Shopware\Models\Article\Supplier');
            $supplier = $repo->findOneBy(['name' => $product->vendor]);
            if ($supplier === null) {
                $supplier = $this->createSupplier($product->vendor);
            }
            $model->setSupplier($supplier);
        }
    }

    /**
     * @param $vendor
     * @return Supplier
     */
    private function createSupplier($vendor)
    {
        $supplier = new Supplier();

        if (is_array($vendor)) {
            $supplier->setName($vendor['name']);
            $supplier->setDescription($vendor['description']);
            if (array_key_exists('url', $vendor) && $vendor['url']) {
                $supplier->setLink($vendor['url']);
            }

            $supplier->setMetaTitle($vendor['page_title']);

            if (array_key_exists('logo_url', $vendor) && $vendor['logo_url']) {
                $this->imageImport->importImageForSupplier($vendor['logo_url'], $supplier);
            }
        } else {
            $supplier->setName($vendor);
        }

        //sets supplier attributes
        $attr = new \Shopware\Models\Attribute\ArticleSupplier();
        $attr->setConnectIsRemote(true);

        $supplier->setAttribute($attr);

        return $supplier;
    }

    /**
     * @param ProductModel $article
     * @param Product $product
     */
    private function applyProductProperties(ProductModel $article, Product $product)
    {
        if (empty($product->properties)) {
            return;
        }

        /** @var Property $firstProperty */
        $firstProperty = reset($product->properties);
        $groupRepo = $this->manager->getRepository(PropertyGroup::class);
        $group = $groupRepo->findOneBy(['name' => $firstProperty->groupName]);

        if (!$group) {
            $group = $this->createPropertyGroup($firstProperty);
        }

        $propertyValues = $article->getPropertyValues();
        $propertyValues->clear();
        $this->manager->persist($article);
        $this->manager->flush();

        $article->setPropertyGroup($group);

        $optionRepo = $this->manager->getRepository(PropertyOption::class);
        $valueRepo = $this->manager->getRepository(PropertyValue::class);

        foreach ($product->properties as $property) {
            $option = $optionRepo->findOneBy(['name' => $property->option]);
            $optionExists = $option instanceof PropertyOption;
            if (!$option) {
                $option = new PropertyOption();
                $option->setName($property->option);
                $option->setFilterable($property->filterable);

                $attribute = new \Shopware\Models\Attribute\PropertyOption();
                $attribute->setPropertyOption($option);
                $attribute->setConnectIsRemote(true);
                $option->setAttribute($attribute);

                $this->manager->persist($option);
                $this->manager->flush($option);
            }

            if (!$optionExists || !$value = $valueRepo->findOneBy(['option' => $option, 'value' => $property->value])) {
                $value = new PropertyValue($option, $property->value);
                $value->setPosition($property->valuePosition);

                $attribute = new \Shopware\Models\Attribute\PropertyValue();
                $attribute->setPropertyValue($value);
                $attribute->setConnectIsRemote(true);
                $value->setAttribute($attribute);

                $this->manager->persist($value);
            }

            if (!$propertyValues->contains($value)) {
                //add only new values
                $propertyValues->add($value);
            }

            $filters = [
                ['property' => 'options.name', 'expression' => '=', 'value' => $property->option],
                ['property' => 'groups.name', 'expression' => '=', 'value' => $property->groupName],
            ];

            $query = $groupRepo->getPropertyRelationQuery($filters, null, 1, 0);
            $relation = $query->getOneOrNullResult();

            if (!$relation) {
                $group->addOption($option);
                $this->manager->persist($group);
                $this->manager->flush($group);
            }
        }

        $article->setPropertyValues($propertyValues);

        $this->manager->persist($article);
        $this->manager->flush();
    }

    /**
     * Read product attributes mapping and set to shopware attribute model
     *
     * @param AttributeModel $detailAttribute
     * @param Product $product
     * @return AttributeModel
     */
    private function applyMarketplaceAttributes(AttributeModel $detailAttribute, Product $product)
    {
        $detailAttribute->setConnectReference($product->sourceId);
        $detailAttribute->setConnectArticleShipping($product->shipping);
        //todo@sb: check if connectAttribute matches position of the marketplace attribute
        array_walk($product->attributes, function ($value, $key) use ($detailAttribute) {
            $shopwareAttribute = $this->marketplaceGateway->findShopwareMappingFor($key);
            if (strlen($shopwareAttribute) > 0) {
                $setter = 'set' . ucfirst($shopwareAttribute);
                $detailAttribute->$setter($value);
            }
        });

        return $detailAttribute;
    }

    /**
     * @param  ConnectAttribute $connectAttribute
     * @param Product $product
     */
    private function setConnectAttributesFromProduct(ConnectAttribute $connectAttribute, Product $product)
    {
        $connectAttribute->setShopId($product->shopId);
        $connectAttribute->setSourceId($product->sourceId);
        $connectAttribute->setExportStatus(null);
        $connectAttribute->setPurchasePrice($product->purchasePrice);
        $connectAttribute->setFixedPrice($product->fixedPrice);
        $connectAttribute->setStream($product->stream);

        // store purchasePriceHash and offerValidUntil
        $connectAttribute->setPurchasePriceHash($product->purchasePriceHash);
        $connectAttribute->setOfferValidUntil($product->offerValidUntil);
    }

    /**
     * @param DetailModel $detail
     * @param Product $product
     */
    private function updateDetailFromProduct(DetailModel $detail, Product $product)
    {
        $detail->setInStock($product->availability);
        $detail->setEan($product->ean);
        $detail->setShippingTime($product->deliveryWorkDays);
        $releaseDate = new \DateTime();
        $releaseDate->setTimestamp($product->deliveryDate);
        $detail->setReleaseDate($releaseDate);
        $detail->setMinPurchase($product->minPurchaseQuantity);
    }

    /**
     * @param DetailModel $detail
     * @param Product $product
     * @param $detailAttribute
     */
    private function detailSetUnit(DetailModel $detail, Product $product, $detailAttribute)
    {
        // if connect product has unit
        // find local unit with units mapping
        // and add to detail model
        if (array_key_exists('unit', $product->attributes) && $product->attributes['unit']) {
            $detailAttribute->setConnectRemoteUnit($product->attributes['unit']);
            if ($this->config->getConfig($product->attributes['unit']) == null) {
                $this->config->setConfig($product->attributes['unit'], '', null, 'units');
            }

            /** @var \ShopwarePlugins\Connect\Components\Utils\UnitMapper $unitMapper */
            $unitMapper = new UnitMapper($this->config, $this->manager);

            $shopwareUnit = $unitMapper->getShopwareUnit($product->attributes['unit']);

            /** @var \Shopware\Models\Article\Unit $unit */
            $unit = $this->helper->getUnit($shopwareUnit);
            $detail->setUnit($unit);
            $detail->setPurchaseUnit($product->attributes['quantity']);
            $detail->setReferenceUnit($product->attributes['ref_quantity']);
        } else {
            $detail->setUnit(null);
            $detail->setPurchaseUnit(null);
            $detail->setReferenceUnit(null);
        }
    }

    /**
     * @param DetailModel $detail
     * @param Product $product
     */
    private function detailSetAttributes(DetailModel $detail, Product $product)
    {
        // set dimension
        if (array_key_exists('dimension', $product->attributes) && $product->attributes['dimension']) {
            $dimension = explode('x', $product->attributes['dimension']);
            $detail->setLen($dimension[0]);
            $detail->setWidth($dimension[1]);
            $detail->setHeight($dimension[2]);
        } else {
            $detail->setLen(null);
            $detail->setWidth(null);
            $detail->setHeight(null);
        }

        // set weight
        if (array_key_exists('weight', $product->attributes) && $product->attributes['weight']) {
            $detail->setWeight($product->attributes['weight']);
        }

        //set package unit
        if (array_key_exists(Product::ATTRIBUTE_PACKAGEUNIT, $product->attributes)) {
            $detail->setPackUnit($product->attributes[Product::ATTRIBUTE_PACKAGEUNIT]);
        }

        //set basic unit
        if (array_key_exists(Product::ATTRIBUTE_BASICUNIT, $product->attributes)) {
            $detail->setMinPurchase($product->attributes[Product::ATTRIBUTE_BASICUNIT]);
        }

        //set manufacturer no.
        if (array_key_exists(Product::ATTRIBUTE_MANUFACTURERNUMBER, $product->attributes)) {
            $detail->setSupplierNumber($product->attributes[Product::ATTRIBUTE_MANUFACTURERNUMBER]);
        }
    }

    /**
     * @param ConnectAttribute $connectAttribute
     * @param Product $product
     */
    private function connectAttributeSetLastUpdate(ConnectAttribute $connectAttribute, Product $product)
    {
        // Whenever a product is updated, store a json encoded list of all fields that are updated optionally
        // This way a customer will be able to apply the most recent changes any time later
        $connectAttribute->setLastUpdate(json_encode([
            'shortDescription' => $product->shortDescription,
            'longDescription' => $product->longDescription,
            'additionalDescription' => $product->additionalDescription,
            'purchasePrice' => $product->purchasePrice,
            'image' => $product->images,
            'variantImages' => $product->variantImages,
            'price' => $product->price * ($product->vat + 1),
            'name' => $product->title,
            'vat' => $product->vat
        ]));
    }

    /**
     * @param ProductModel $model
     * @param array $categories
     */
    private function categoryDenormalization(ProductModel $model, array $categories)
    {
        $this->categoryDenormalization->disableTransactions();
        foreach ($categories as $category) {
            $this->categoryDenormalization->addAssignment($model->getId(), $category);
            $this->manager->getConnection()->executeQuery(
                'INSERT IGNORE INTO `s_articles_categories` (`articleID`, `categoryID`) VALUES (?,?)',
                [$model->getId(), $category]
            );
            $parentId =$this->manager->getConnection()->fetchColumn(
                'SELECT parent FROM `s_categories` WHERE id = ?',
                [$category]
            );
            $this->categoryDenormalization->removeAssignment($model->getId(), $parentId);
            $this->manager->getConnection()->executeQuery(
                'DELETE FROM `s_articles_categories` WHERE `articleID` = ? AND `categoryID` = ?',
                [$model->getId(), $parentId]
            );
        }
        $this->categoryDenormalization->enableTransactions();
    }

    /**
     * @param Product $product
     * @return ProductStream
     */
    private function getOrCreateStream(Product $product)
    {
        /** @var ProductStreamRepository $repo */
        $repo = $this->manager->getRepository(ProductStreamAttribute::class);
        $stream = $repo->findConnectByName($product->stream);

        if (!$stream) {
            $stream = new ProductStream();
            $stream->setName($product->stream);
            $stream->setType(ProductStreamService::STATIC_STREAM);
            $stream->setSorting(json_encode(
                [ReleaseDateSorting::class => ['direction' => 'desc']]
            ));

            //add attributes
            $attribute = new \Shopware\Models\Attribute\ProductStream();
            $attribute->setProductStream($stream);
            $attribute->setConnectIsRemote(true);
            $stream->setAttribute($attribute);

            $this->manager->persist($attribute);
            $this->manager->persist($stream);
            $this->manager->flush();
        }

        return $stream;
    }

    /**
     * @param ProductStream $stream
     * @param ProductModel $article
     * @throws \Doctrine\DBAL\DBALException
     */
    private function addProductToStream(ProductStream $stream, ProductModel $article)
    {
        $conn = $this->manager->getConnection();
        $sql = 'INSERT INTO `s_product_streams_selection` (`stream_id`, `article_id`)
                VALUES (:streamId, :articleId)
                ON DUPLICATE KEY UPDATE stream_id = :streamId, article_id = :articleId';
        $stmt = $conn->prepare($sql);
        $stmt->execute([':streamId' => $stream->getId(), ':articleId' => $article->getId()]);
    }

    /**
     * Set detail purchase price with plain SQL
     * Entity usage throws exception when error handlers are disabled
     *
     * @param ProductModel $article
     * @param DetailModel $detail
     * @param Product $product
     * @throws \Doctrine\DBAL\DBALException
     */
    private function setPrice(ProductModel $article, DetailModel $detail, Product $product)
    {
        // set price via plain SQL because shopware throws exception
        // undefined index: key when error handler is disabled
        $customerGroup = $this->helper->getDefaultCustomerGroup();

        if (!empty($product->priceRanges)) {
            $this->setPriceRange($article, $detail, $product->priceRanges, $customerGroup);

            return;
        }

        $id = $this->manager->getConnection()->fetchColumn(
            'SELECT id FROM `s_articles_prices`
              WHERE `pricegroup` = ? AND `from` = ? AND `to` = ? AND `articleID` = ? AND `articledetailsID` = ?',
            [$customerGroup->getKey(), 1, 'beliebig', $article->getId(), $detail->getId()]
        );

        // todo@sb: test update prices
        if ($id > 0) {
            $this->manager->getConnection()->executeQuery(
                'UPDATE `s_articles_prices` SET `price` = ?, `baseprice` = ? WHERE `id` = ?',
                [$product->price, $product->purchasePrice, $id]
            );
        } else {
            $this->manager->getConnection()->executeQuery(
                'INSERT INTO `s_articles_prices`(`pricegroup`, `from`, `to`, `articleID`, `articledetailsID`, `price`, `baseprice`)
              VALUES (?, 1, "beliebig", ?, ?, ?, ?);',
                [
                    $customerGroup->getKey(),
                    $article->getId(),
                    $detail->getId(),
                    $product->price,
                    $product->purchasePrice
                ]
            );
        }
    }

    /**
     * @param ProductModel $article
     * @param DetailModel $detail
     * @param array $priceRanges
     * @param Group $group
     * @throws \Doctrine\DBAL\ConnectionException
     * @throws \Exception
     */
    private function setPriceRange(ProductModel $article, DetailModel $detail, array $priceRanges, Group $group)
    {
        $this->manager->getConnection()->beginTransaction();

        try {
            // We always delete the prices,
            // because we can not know which record is update
            $this->manager->getConnection()->executeQuery(
                'DELETE FROM `s_articles_prices` WHERE `articleID` = ? AND `articledetailsID` = ?',
                [$article->getId(), $detail->getId()]
            );

            /** @var PriceRange $priceRange */
            foreach ($priceRanges as $priceRange) {
                $priceTo = $priceRange->to == PriceRange::ANY ? 'beliebig' : $priceRange->to;

                //todo: maybe batch insert if possible?
                $this->manager->getConnection()->executeQuery(
                    'INSERT INTO `s_articles_prices`(`pricegroup`, `from`, `to`, `articleID`, `articledetailsID`, `price`)
                      VALUES (?, ?, ?, ?, ?, ?);',
                    [
                        $group->getKey(),
                        $priceRange->from,
                        $priceTo,
                        $article->getId(),
                        $detail->getId(),
                        $priceRange->price
                    ]
                );
            }
            $this->manager->getConnection()->commit();
        } catch (\Exception $e) {
            $this->manager->getConnection()->rollBack();
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * Set detail purchase price with plain SQL
     * Entity usage throws exception when error handlers are disabled
     *
     * @param DetailModel $detail
     * @param float $purchasePrice
     * @param Group $defaultGroup
     * @throws \Doctrine\DBAL\DBALException
     */
    private function setPurchasePrice(DetailModel $detail, $purchasePrice, Group $defaultGroup)
    {
        if (method_exists($detail, 'setPurchasePrice')) {
            $this->manager->getConnection()->executeQuery(
                'UPDATE `s_articles_details` SET `purchaseprice` = ? WHERE `id` = ?',
                [$purchasePrice, $detail->getId()]
            );
        } else {
            $id = $this->manager->getConnection()->fetchColumn(
                'SELECT id FROM `s_articles_prices`
              WHERE `pricegroup` = ? AND `from` = ? AND `to` = ? AND `articleID` = ? AND `articledetailsID` = ?',
                [$defaultGroup->getKey(), 1, 'beliebig', $detail->getArticleId(), $detail->getId()]
            );

            if ($id > 0) {
                $this->manager->getConnection()->executeQuery(
                    'UPDATE `s_articles_prices` SET `baseprice` = ? WHERE `id` = ?',
                    [$purchasePrice, $id]
                );
            } else {
                $this->manager->getConnection()->executeQuery(
                    'INSERT INTO `s_articles_prices`(`pricegroup`, `from`, `to`, `articleID`, `articledetailsID`, `baseprice`)
              VALUES (?, 1, "beliebig", ?, ?, ?);',
                    [$defaultGroup->getKey(), $detail->getArticleId(), $detail->getId(), $purchasePrice]
                );
            }
        }
    }

    /**
     * Adds translation record for given article
     *
     * @param ProductModel $article
     * @param Product $sdkProduct
     */
    private function addArticleTranslations(ProductModel $article, Product $sdkProduct)
    {
        /** @var \Shopware\Connect\Struct\Translation $translation */
        foreach ($sdkProduct->translations as $key => $translation) {
            /** @var \Shopware\Models\Shop\Locale $locale */
            $locale = $this->getLocaleRepository()->findOneBy(['locale' => LocaleMapper::getShopwareLocale($key)]);
            /** @var \Shopware\Models\Shop\Shop $shop */
            $shop = $this->getShopRepository()->findOneBy(['locale' => $locale]);
            if (!$shop) {
                continue;
            }

            $this->productTranslationsGateway->addArticleTranslation($translation, $article->getId(), $shop->getId());
        }
    }

    /**
     * dsadsa
     * @return \Shopware\Components\Model\ModelRepository
     */
    private function getLocaleRepository()
    {
        if (!$this->localeRepository) {
            $this->localeRepository = $this->manager->getRepository('Shopware\Models\Shop\Locale');
        }

        return $this->localeRepository;
    }

    private function getShopRepository()
    {
        if (!$this->shopRepository) {
            $this->shopRepository = $this->manager->getRepository('Shopware\Models\Shop\Shop');
        }

        return $this->shopRepository;
    }

    /**
     * Delete product or product variant with given shopId and sourceId.
     *
     * Only the combination of both identifies a product uniquely. Do NOT
     * delete products just by their sourceId.
     *
     * You might receive delete requests for products, which are not available
     * in your shop. Just ignore them.
     *
     * @param string $shopId
     * @param string $sourceId
     * @return void
     */
    public function delete($shopId, $sourceId)
    {
        $detail = $this->helper->getArticleDetailModelByProduct(new Product([
            'shopId' => $shopId,
            'sourceId' => $sourceId,
        ]));
        if ($detail === null) {
            return;
        }

        $this->deleteDetail($detail);
    }

    public function update($shopId, $sourceId, ProductUpdate $product)
    {
        // find article detail id
        $articleDetailId = $this->manager->getConnection()->fetchColumn(
            'SELECT article_detail_id FROM s_plugin_connect_items WHERE source_id = ? AND shop_id = ?',
            [$sourceId, $shopId]
        );

        $this->eventManager->notify(
            'Connect_Merchant_Update_GeneralProductInformation',
            [
                'subject' => $this,
                'shopId' => $shopId,
                'sourceId' => $sourceId,
                'articleDetailId' => $articleDetailId
            ]
        );

        // update purchasePriceHash, offerValidUntil and purchasePrice in connect attribute
        $this->manager->getConnection()->executeUpdate(
            'UPDATE s_plugin_connect_items SET purchase_price_hash = ?, offer_valid_until = ?, purchase_price = ?
            WHERE source_id = ? AND shop_id = ?',
            [
                $product->purchasePriceHash,
                $product->offerValidUntil,
                $product->purchasePrice,
                $sourceId,
                $shopId,
            ]
        );

        // update stock in article detail
        // update prices
        // if purchase price is stored in article detail
        // update it together with stock
        // since shopware 5.2
        if (method_exists('Shopware\Models\Article\Detail', 'getPurchasePrice')) {
            $this->manager->getConnection()->executeUpdate(
                'UPDATE s_articles_details SET instock = ?, purchaseprice = ? WHERE id = ?',
                [$product->availability, $product->purchasePrice, $articleDetailId]
            );
        } else {
            $this->manager->getConnection()->executeUpdate(
                'UPDATE s_articles_details SET instock = ? WHERE id = ?',
                [$product->availability, $articleDetailId]
            );
        }
        $this->manager->getConnection()->executeUpdate(
            "UPDATE s_articles_prices SET price = ?, baseprice = ? WHERE articledetailsID = ? AND pricegroup = 'EK'",
            [$product->price, $product->purchasePrice, $articleDetailId]
        );
    }

    public function changeAvailability($shopId, $sourceId, $availability)
    {
        // find article detail id
        $articleDetailId = $this->manager->getConnection()->fetchColumn(
            'SELECT article_detail_id FROM s_plugin_connect_items WHERE source_id = ? AND shop_id = ?',
            [$sourceId, $shopId]
        );

        $this->eventManager->notify(
            'Connect_Merchant_Update_GeneralProductInformation',
            [
                'subject' => $this,
                'shopId' => $shopId,
                'sourceId' => $sourceId,
                'articleDetailId' => $articleDetailId
            ]
        );

        // update stock in article detail
        $this->manager->getConnection()->executeUpdate(
            'UPDATE s_articles_details SET instock = ? WHERE id = ?',
            [$availability, $articleDetailId]
        );
    }

    /**
     * @inheritDoc
     */
    public function makeMainVariant($shopId, $sourceId, $groupId)
    {
        //find article detail which should be selected as main one
        $newMainDetail = $this->helper->getConnectArticleDetailModel($sourceId, $shopId);
        if (!$newMainDetail) {
            return;
        }

        /** @var \Shopware\Models\Article\Article $article */
        $article = $newMainDetail->getArticle();

        $this->eventManager->notify(
            'Connect_Merchant_Update_ProductMainVariant_Before',
            [
                'subject' => $this,
                'shopId' => $shopId,
                'sourceId' => $sourceId,
                'articleId' => $article->getId(),
                'articleDetailId' => $newMainDetail->getId()
            ]
        );

        // replace current main detail with new one
        $currentMainDetail = $article->getMainDetail();
        $currentMainDetail->setKind(2);
        $newMainDetail->setKind(1);
        $article->setMainDetail($newMainDetail);

        $this->manager->persist($newMainDetail);
        $this->manager->persist($currentMainDetail);
        $this->manager->persist($article);
        $this->manager->flush();
    }

    /**
     * Updates the status of an Order
     *
     * @param string $localOrderId
     * @param string $orderStatus
     * @param string $trackingNumber
     * @return void
     */
    public function updateOrderStatus($localOrderId, $orderStatus, $trackingNumber)
    {
        if ($this->config->getConfig('updateOrderStatus') == 1) {
            $this->updateDeliveryStatus($localOrderId, $orderStatus);
        }

        if ($trackingNumber) {
            $this->updateTrackingNumber($localOrderId, $trackingNumber);
        }
    }

    /**
     * @param string $localOrderId
     * @param string $orderStatus
     */
    private function updateDeliveryStatus($localOrderId, $orderStatus)
    {
        $status = false;
        if ($orderStatus === OrderStatus::STATE_IN_PROCESS) {
            $status = Status::ORDER_STATE_PARTIALLY_DELIVERED;
        } elseif ($orderStatus === OrderStatus::STATE_DELIVERED) {
            $status = Status::ORDER_STATE_COMPLETELY_DELIVERED;
        }

        if ($status) {
            $this->manager->getConnection()->executeQuery(
                'UPDATE s_order 
                SET status = :orderStatus
                WHERE ordernumber = :orderNumber',
                [
                    ':orderStatus' => $status,
                    ':orderNumber' => $localOrderId
                ]
            );
        }
    }

    /**
     * @param string $localOrderId
     * @param string $trackingNumber
     */
    private function updateTrackingNumber($localOrderId, $trackingNumber)
    {
        $currentTrackingCode = $this->manager->getConnection()->fetchColumn(
            'SELECT trackingcode
            FROM s_order
            WHERE ordernumber = :orderNumber',
            [
                ':orderNumber' => $localOrderId
            ]
        );

        if (!$currentTrackingCode) {
            $newTracking = $trackingNumber;
        } else {
            $newTracking = $this->combineTrackingNumbers($trackingNumber, $currentTrackingCode);
        }

        $this->manager->getConnection()->executeQuery(
            'UPDATE s_order 
            SET trackingcode = :trackingCode
            WHERE ordernumber = :orderNumber',
            [
                ':trackingCode' => $newTracking,
                ':orderNumber' => $localOrderId
            ]
        );
    }

    /**
     * @param string $newTrackingCode
     * @param string $currentTrackingCode
     * @return string
     */
    private function combineTrackingNumbers($newTrackingCode, $currentTrackingCode)
    {
        $currentTrackingCodes = $this->getTrackingNumberAsArray($currentTrackingCode);
        $newTrackingCodes = $this->getTrackingNumberAsArray($newTrackingCode);
        $newTrackingCodes = array_unique(array_merge($currentTrackingCodes, $newTrackingCodes));
        $newTracking = implode(',', $newTrackingCodes);

        return $newTracking;
    }

    /**
     * @param string $trackingCode
     * @return string[]
     */
    private function getTrackingNumberAsArray($trackingCode)
    {
        if (strpos($trackingCode, ',') !== false) {
            return explode(',', $trackingCode);
        }

        return [$trackingCode];
    }

    /**
     * @param Product $product
     * @param ProductModel $model
     */
    private function saveVat(Product $product, ProductModel $model)
    {
        if ($product->vat !== null) {
            $repo = $this->manager->getRepository(Tax::class);
            $taxRate = round($product->vat * 100, 2);
            /** @var \Shopware\Models\Tax\Tax $tax */
            $tax = $repo->findOneBy(['tax' => $taxRate]);
            if (!$tax) {
                $tax = new Tax();
                $tax->setTax($taxRate);
                //this is to get rid of zeroes behind the decimal point
                $name = strval(round($taxRate, 2)) . '%';
                $tax->setName($name);
                $this->manager->persist($tax);
            }
            $model->setTax($tax);
        }
    }

    /**
     * @param int $articleId
     * @param Product $product
     */
    private function applyCrossSelling($articleId, Product $product)
    {
        $this->deleteRemovedRelations($articleId, $product);
        $this->storeCrossSellingInformationInverseSide($articleId, $product->sourceId, $product->shopId);
        if ($product->similar || $product->related) {
            $this->storeCrossSellingInformationOwningSide($articleId, $product);
        }
    }

    /**
     * @param int $articleId
     * @param Product $product
     */
    private function storeCrossSellingInformationOwningSide($articleId, $product)
    {
        foreach ($product->related as $relatedId) {
            $this->insertNewRelations($articleId, $product->shopId, $relatedId, self::RELATION_TYPE_RELATED);
        }

        foreach ($product->similar as $similarId) {
            $this->insertNewRelations($articleId, $product->shopId, $similarId, self::RELATION_TYPE_SIMILAR);
        }
    }

    /**
     * @param int $articleId
     * @param int $shopId
     * @param int $relatedId
     * @param string $relationType
     */
    private function insertNewRelations($articleId, $shopId, $relatedId, $relationType)
    {
        $inserted = false;
        try {
            $this->manager->getConnection()->executeQuery(
                'INSERT INTO s_plugin_connect_article_relations (article_id, shop_id, related_article_local_id, relationship_type) VALUES (?, ?, ?, ?)',
                [$articleId, $shopId, $relatedId, $relationType]
            );
            $inserted = true;
        } catch (\Doctrine\DBAL\DBALException $e) {
            // No problems here. Just means that the row already existed.
        }

        //outside of try catch because we don't want to catch exceptions -> this method should not throw any
        if ($inserted) {
            $relatedLocalId = $this->manager->getConnection()->fetchColumn(
                'SELECT article_id FROM s_plugin_connect_items WHERE shop_id = ? AND source_id = ?',
                [$shopId, $relatedId]
            );
            if ($relatedLocalId) {
                $this->manager->getConnection()->executeQuery(
                    "INSERT IGNORE INTO s_articles_$relationType (articleID, relatedarticle) VALUES (?, ?)",
                    [$articleId, $relatedLocalId]
                );
            }
        }
    }

    /**
     * @param int $articleId
     * @param string $sourceId
     * @param int $shopId
     */
    private function storeCrossSellingInformationInverseSide($articleId, $sourceId, $shopId)
    {
        $relatedArticles = $this->manager->getConnection()->fetchAll(
            'SELECT article_id, relationship_type FROM s_plugin_connect_article_relations WHERE shop_id = ? AND related_article_local_id = ?',
            [$shopId, $sourceId]
        );

        foreach ($relatedArticles as $relatedArticle) {
            $relationType = $relatedArticle['relationship_type'];
            $this->manager->getConnection()->executeQuery(
                "INSERT IGNORE INTO s_articles_$relationType (articleID, relatedarticle) VALUES (?, ?)",
                [$relatedArticle['article_id'], $articleId]
            );
        }
    }

    /**
     * @param $articleId
     * @param $product
     */
    private function deleteRemovedRelations($articleId, $product)
    {
        if (count($product->related) > 0) {
            $this->manager->getConnection()->executeQuery(
                'DELETE FROM s_plugin_connect_article_relations WHERE article_id = ? AND shop_id = ? AND related_article_local_id NOT IN (?) AND relationship_type = ?',
                [$articleId, $product->shopId, $product->related, self::RELATION_TYPE_RELATED],
                [\PDO::PARAM_INT, \PDO::PARAM_INT, \Doctrine\DBAL\Connection::PARAM_INT_ARRAY, \PDO::PARAM_STR]
            );

            $oldRelatedIds = $this->manager->getConnection()->executeQuery(
                'SELECT ar.id 
                FROM s_articles_relationships AS ar
                INNER JOIN s_plugin_connect_items AS ci ON ar.relatedarticle = ci.article_id
                WHERE ar.articleID = ? AND ci.shop_id = ? AND ci.source_id NOT IN (?)',
                [$articleId, $product->shopId, $product->related],
                [\PDO::PARAM_INT, \PDO::PARAM_INT, \Doctrine\DBAL\Connection::PARAM_INT_ARRAY]
            )
                ->fetchAll(\PDO::FETCH_COLUMN);
        } else {
            $this->manager->getConnection()->executeQuery(
                'DELETE FROM s_plugin_connect_article_relations WHERE article_id = ? AND shop_id = ? AND relationship_type = ?',
                [$articleId, $product->shopId, self::RELATION_TYPE_RELATED]
            );

            $oldRelatedIds = $this->manager->getConnection()->executeQuery(
                'SELECT ar.id 
                FROM s_articles_relationships AS ar
                INNER JOIN s_plugin_connect_items AS ci ON ar.relatedarticle = ci.article_id
                WHERE ar.articleID = ? AND ci.shop_id = ?',
                [$articleId, $product->shopId]
            )
                ->fetchAll(\PDO::FETCH_COLUMN);
        }

        $this->manager->getConnection()->executeQuery(
            'DELETE FROM s_articles_relationships WHERE id IN (?)',
            [$oldRelatedIds],
            [\Doctrine\DBAL\Connection::PARAM_INT_ARRAY]
        );

        if (count($product->similar) > 0) {
            $this->manager->getConnection()->executeQuery(
                'DELETE FROM s_plugin_connect_article_relations WHERE article_id = ? AND shop_id = ? AND related_article_local_id NOT IN (?) AND relationship_type = ?',
                [$articleId, $product->shopId, $product->similar, self::RELATION_TYPE_SIMILAR],
                [\PDO::PARAM_INT, \PDO::PARAM_INT, \Doctrine\DBAL\Connection::PARAM_INT_ARRAY, \PDO::PARAM_STR]
            );

            $oldSimilarIds = $this->manager->getConnection()->executeQuery(
                'SELECT ar.id 
                FROM s_articles_similar AS ar
                INNER JOIN s_plugin_connect_items AS ci ON ar.relatedarticle = ci.article_id
                WHERE ar.articleID = ? AND ci.shop_id = ? AND ci.source_id NOT IN (?)',
                [$articleId, $product->shopId, $product->similar],
                [\PDO::PARAM_INT, \PDO::PARAM_INT, \Doctrine\DBAL\Connection::PARAM_INT_ARRAY]
            )
                ->fetchAll(\PDO::FETCH_COLUMN);
        } else {
            $this->manager->getConnection()->executeQuery(
                'DELETE FROM s_plugin_connect_article_relations WHERE article_id = ? AND shop_id = ? AND relationship_type = ?',
                [$articleId, $product->shopId, self::RELATION_TYPE_SIMILAR]
            );

            $oldSimilarIds = $this->manager->getConnection()->executeQuery(
                'SELECT ar.id 
                FROM s_articles_similar AS ar
                INNER JOIN s_plugin_connect_items AS ci ON ar.relatedarticle = ci.article_id
                WHERE ar.articleID = ? AND ci.shop_id = ?',
                [$articleId, $product->shopId]
            )
                ->fetchAll(\PDO::FETCH_COLUMN);
        }

        $this->manager->getConnection()->executeQuery(
            'DELETE FROM s_articles_similar WHERE id IN (?)',
            [$oldSimilarIds],
            [\Doctrine\DBAL\Connection::PARAM_INT_ARRAY]
        );
    }

    /**
     * @param Property $property
     * @return PropertyGroup
     */
    private function createPropertyGroup(Property $property)
    {
        $group = new PropertyGroup();
        $group->setName($property->groupName);
        $group->setComparable($property->comparable);
        $group->setSortMode($property->sortMode);
        $group->setPosition($property->groupPosition);

        $attribute = new \Shopware\Models\Attribute\PropertyGroup();
        $attribute->setPropertyGroup($group);
        $attribute->setConnectIsRemote(true);
        $group->setAttribute($attribute);

        $this->manager->persist($attribute);
        $this->manager->persist($group);
        $this->manager->flush();

        return $group;
    }
}
