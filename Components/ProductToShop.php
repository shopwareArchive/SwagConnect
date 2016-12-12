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

namespace ShopwarePlugins\Connect\Components;
use Shopware\Connect\Gateway;
use Shopware\Connect\ProductToShop as ProductToShopBase,
    Shopware\Connect\Struct\Product,
    Shopware\Models\Article\Article as ProductModel,
    Shopware\Models\Article\Detail as DetailModel,
    Shopware\Models\Attribute\Article as AttributeModel,
    Shopware\Components\Model\ModelManager,
    Doctrine\ORM\Query;
use Shopware\Connect\SDK;
use Shopware\Connect\Struct\PriceRange;
use Shopware\Connect\Struct\ProductUpdate;
use Shopware\Models\Customer\Group;
use ShopwarePlugins\Connect\Components\Translations\LocaleMapper;
use ShopwarePlugins\Connect\Components\Gateway\ProductTranslationsGateway;
use ShopwarePlugins\Connect\Components\Marketplace\MarketplaceGateway;
use ShopwarePlugins\Connect\Components\Utils\UnitMapper;
use Shopware\CustomModels\Connect\Attribute as ConnectAttribute;
use Shopware\Models\Article\Image;
use Shopware\Models\Article\Price;
use Shopware\Models\Article\Supplier;

/**
 * The interface for products imported *from* connect *to* the local shop
 *
 * @category  Shopware
 * @package   Shopware\Plugins\SwagConnect
 */
class ProductToShop implements ProductToShopBase
{
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

    /** @var  \Shopware\Models\Shop\Repository */
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
     * @param Helper $helper
     * @param ModelManager $manager
     * @param ImageImport $imageImport
     * @param \ShopwarePlugins\Connect\Components\Config $config
     * @param VariantConfigurator $variantConfigurator
     * @param \ShopwarePlugins\Connect\Components\Marketplace\MarketplaceGateway $marketplaceGateway
     * @param ProductTranslationsGateway $productTranslationsGateway
     * @param \ShopwarePlugins\Connect\Components\CategoryResolver
     * @param \Shopware\Connect\Gateway
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
        Gateway $connectGateway
    )
    {
        $this->helper = $helper;
        $this->manager = $manager;
        $this->config = $config;
        $this->imageImport = $imageImport;
        $this->variantConfigurator = $variantConfigurator;
        $this->marketplaceGateway = $marketplaceGateway;
        $this->productTranslationsGateway = $productTranslationsGateway;
        $this->categoryResolver = $categoryResolver;
        $this->connectGateway = $connectGateway;
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
        // todo@dn: Set dummy values and make product inactive
        if (empty($product->title) || empty($product->vendor)) {
            return;
        }

        $detail = $this->helper->getArticleDetailModelByProduct($product);
        $isMainVariant = false;

        if ($detail === null) {
            $active = $this->config->getConfig('activateProductsAutomatically', false) ? true : false;
            if ($product->groupId > 0) {
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

            $detail = new DetailModel();
            if (!empty($product->sku)) {
                $detail->setNumber('SC-' . $product->shopId . '-' . $product->sku);
            } else {
                $detail->setNumber('SC-' . $product->shopId . '-' . $product->sourceId);
            }

            $detail->setActive($model->getActive());

            $detail->setArticle($model);
            if (!empty($product->variant)) {
                $this->variantConfigurator->configureVariantAttributes($product, $detail);
            }

            $categories = $this->categoryResolver->resolve($product->categories);
            $model->setCategories($categories);
        } else {
            $model = $detail->getArticle();
            // fix for isMainVariant flag
            // in connect attribute table
            $mainDetail = $model->getMainDetail();
            if ($detail->getId() === $mainDetail->getId()) {
                $isMainVariant = true;
            }
        }

        $connectAttribute = $this->helper->getConnectAttributeByModel($detail) ?: new ConnectAttribute;
        // configure main variant and groupId
        if ($isMainVariant === true) {
            $connectAttribute->setIsMainVariant(true);
        }
        $connectAttribute->setGroupId($product->groupId);

        $detailAttribute = $detail->getAttribute() ?: new AttributeModel();

        list($updateFields, $flag) = $this->getUpdateFields($model, $detail, $connectAttribute, $product);
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

        if ($product->vat !== null) {
            $repo = $this->manager->getRepository('Shopware\Models\Tax\Tax');
            $tax = round($product->vat * 100, 2);
            $tax = $repo->findOneBy(array('tax' => $tax));
            $model->setTax($tax);
        }

        if ($product->vendor !== null) {
            $repo = $this->manager->getRepository('Shopware\Models\Article\Supplier');
            $supplier = $repo->findOneBy(array('name' => $product->vendor));
            if ($supplier === null) {
                $supplier = $this->createSupplier($product->vendor);
            }
            $model->setSupplier($supplier);
        }

        // apply marketplace attributes
        $detailAttribute = $this->applyMarketplaceAttributes($detailAttribute, $product);

        $connectAttribute->setShopId($product->shopId);
        $connectAttribute->setSourceId($product->sourceId);
        $connectAttribute->setExportStatus(null);
        $connectAttribute->setPurchasePrice($product->purchasePrice);
        $connectAttribute->setFixedPrice($product->fixedPrice);
        $connectAttribute->setStream($product->stream);

        // store product categories to connect attribute
        $connectAttribute->setCategory($product->categories);

        $connectAttribute->setLastUpdateFlag($flag);
        // store purchasePriceHash and offerValidUntil
        $connectAttribute->setPurchasePriceHash($product->purchasePriceHash);
        $connectAttribute->setOfferValidUntil($product->offerValidUntil);

        $detail->setInStock($product->availability);
        $detail->setEan($product->ean);
        $detail->setShippingTime($product->deliveryWorkDays);
        $releaseDate = new \DateTime();
        $releaseDate->setTimestamp($product->deliveryDate);
        $detail->setReleaseDate($releaseDate);
        $detail->setMinPurchase($product->minPurchaseQuantity);

        // some shops have feature "sell not in stock",
        // then end customer should be able to by the product with stock = 0
        $shopConfiguration = $this->connectGateway->getShopConfiguration($product->shopId);
        if ($shopConfiguration && $shopConfiguration->sellNotInStock) {
            $model->setLastStock(false);
        } else {
            $model->setLastStock(true);
        }

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

        // Whenever a product is updated, store a json encoded list of all fields that are updated optionally
        // This way a customer will be able to apply the most recent changes any time later
        $connectAttribute->setLastUpdate(json_encode(array(
            'shortDescription' => $product->shortDescription,
            'longDescription' => $product->longDescription,
            'purchasePrice' => $product->purchasePrice,
            'image' => $product->images,
            'price' => $product->price * ($product->vat + 1),
            'name' => $product->title,
            'vat' => $product->vat
        )));

        if ($model->getMainDetail() === null) {
            $model->setMainDetail($detail);
        }

        if ($detail->getAttribute() === null) {
            $detail->setAttribute($detailAttribute);
            $detailAttribute->setArticle($model);
        }

        $connectAttribute->setArticle($model);
        $connectAttribute->setArticleDetail($detail);
        $this->manager->persist($connectAttribute);

        $this->manager->persist($detail);

        // some articles from connect have long sourceId
        // like OXID articles. They use md5 hash, but it is not supported
        // in shopware.
        if (strlen($detail->getNumber()) > 30) {
            $detail->setNumber('SC-' . $product->shopId . '-' . $detail->getId());

            $this->manager->persist($detail);
            $this->manager->flush($detail);
        }

        $this->manager->flush();

        $defaultCustomerGroup = $this->helper->getDefaultCustomerGroup();
        // Only set prices, if fixedPrice is active or price updates are configured
        if (count($detail->getPrices()) == 0 || $connectAttribute->getFixedPrice() || $updateFields['price']) {
            $this->setPrice($model, $detail, $product);
        }
        // If the price is not being update, update the purchasePrice anyway
        $this->setPurchasePrice($detail, $product->purchasePrice, $defaultCustomerGroup);

        $this->manager->clear();

        $this->addArticleTranslations($model, $product);

        //clear cache for that article
        $this->helper->clearArticleCache($model->getId());

        if ($updateFields['image']) {
            // Reload the model in order to not to work an the already flushed model
            $model = $this->helper->getArticleModelByProduct($product);
            $this->imageImport->importImagesForArticle($product->images, $model);
        }
        $this->categoryResolver->storeRemoteCategories($product->categories, $model->getId());
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
            [$customerGroup->getKey(), 1, "beliebig", $article->getId(), $detail->getId()]
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
                [$customerGroup->getKey(), $article->getId(), $detail->getId(), $product->price, $product->purchasePrice]
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

            $locale = $this->getLocaleRepository()->findOneBy(array('locale' => LocaleMapper::getShopwareLocale($key)));
            /** @var \Shopware\Models\Shop\Shop $shop */
            $shop = $this->getShopRepository()->findOneBy(array('locale' => $locale));
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
        $detail = $this->helper->getArticleDetailModelByProduct(new Product(array(
            'shopId' => $shopId,
            'sourceId' => $sourceId,
        )));
        if($detail === null) {
            return;
        }

        $article = $detail->getArticle();
        $isOnlyOneVariant = false;
        if (count($article->getDetails()) === 1) {
            $isOnlyOneVariant = true;
        }

        // Not sure why, but the Attribute can be NULL
        $attribute = $this->helper->getConnectAttributeByModel($detail);
        if ($attribute) {
            $this->manager->remove($attribute);
        }

        // if removed variant is main variant
        // find first variant which is not main and mark it
        if ($detail->getKind() === 1) {
            /** @var \Shopware\Models\Article\Detail $variant */
            foreach ($article->getDetails() as $variant) {
                if ($variant->getId() != $detail->getId()) {
                    $variant->setKind(1);
                    $article->setMainDetail($variant);
                    $this->manager->persist($article);
                    $this->manager->persist($variant);
                    break;
                }
            }
        }

        $this->manager->remove($detail);
        if ($isOnlyOneVariant === true) {
            $article->getDetails()->clear();
            $this->manager->remove($article);
        }

        $this->manager->flush();
        $this->manager->clear();
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
        $output = array();
        foreach ($fields as $key => $field) {
            // Don't handle the imageInitialImport flag
            if ($field == 'imageInitialImport') {
                continue;
            }

            // If this is a new product
            if (!$model->getId() && $field == 'image' && !$this->config->getConfig('importImagesOnFirstImport', false)) {
                $output[$field] = false;
                $flag |= $flagsByName['imageInitialImport'];
                continue;
            }

            $updateAllowed = $this->isFieldUpdateAllowed($field, $model, $attribute);
            $output[$field] = $updateAllowed;
            if (!$updateAllowed && $this->hasFieldChanged($field, $model, $detail, $attribute, $product)) {
                $flag |= $key;
            }
        }

        return array($output, $flag);
    }

    /**
     * Determine if a given field has changed
     *
     * @param $field
     * @param ProductModel $model
     * @param DetailModel $detail
     * @param AttributeModel $attribute
     * @param Product $product
     * @return bool
     */
    public function hasFieldChanged($field, ProductModel $model, DetailModel $detail, AttributeModel $attribute, Product $product)
    {

        switch ($field) {
            case 'shortDescription':
                return $model->getDescription() != $product->shortDescription;
            case 'longDescription':
                return $model->getDescriptionLong() != $product->longDescription;
            case 'name':
                return $model->getName() != $product->title;
            case 'image':
                return count($model->getImages()) != count($product->images);
            case 'price':
                $prices = $detail->getPrices();
                if (empty($prices)) {
                    return true;
                }
                return $prices->first()->getPrice() != $product->price;
        }

        throw new \InvalidArgumentException('Unrecognized field');
    }

    /**
     * Helper method to determine if a given $fields may/must be updated.
     * This method will check for the model->id in order to determine, if it is a new entity. Therefore
     * this method cannot be used after the model in question was already flushed.
     *
     * @param $field
     * @param $model ProductModel
     * @param $attribute ConnectAttribute
     * @return bool|null
     * @throws \RuntimeException
     */
    public function isFieldUpdateAllowed($field, ProductModel $model, ConnectAttribute $attribute)
    {
        $allowed = array(
            'ShortDescription',
            'LongDescription',
            'Image',
            'Price',
            'Name',
        );

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
        array_walk($product->attributes, function($value, $key) use ($detailAttribute) {
            $shopwareAttribute = $this->marketplaceGateway->findShopwareMappingFor($key);
            if (strlen($shopwareAttribute) > 0) {
                $setter = 'set' . ucfirst($shopwareAttribute);
                $detailAttribute->$setter($value);
            }
        });

        return $detailAttribute;
    }

    /**
     * @param $vendor
     * @return Supplier
     */
    private function createSupplier($vendor)
    {
        $supplier = new Supplier();

        if (is_array($vendor)){
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
                [$defaultGroup->getKey(), 1, "beliebig", $detail->getArticleId(), $detail->getId()]
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

    public function update($shopId, $sourceId, ProductUpdate $product)
    {
        // find article detail id
        $articleDetailId = $this->manager->getConnection()->fetchColumn(
            'SELECT article_detail_id FROM s_plugin_connect_items WHERE source_id = ? AND shop_id = ?',
            array($sourceId, $shopId)
        );

        // update purchasePriceHash, offerValidUntil and purchasePrice in connect attribute
        $this->manager->getConnection()->executeUpdate(
            'UPDATE s_plugin_connect_items SET purchase_price_hash = ?, offer_valid_until = ?, purchase_price = ?
            WHERE source_id = ? AND shop_id = ?',
            array(
                $product->purchasePriceHash,
                $product->offerValidUntil,
                $product->purchasePrice,
                $sourceId,
                $shopId,
            )
        );

        // update stock in article detail
        // update prices
        // if purchase price is stored in article detail
        // update it together with stock
        // since shopware 5.2
        if (method_exists('Shopware\Models\Article\Detail', 'getPurchasePrice')) {
            $this->manager->getConnection()->executeUpdate(
                'UPDATE s_articles_details SET instock = ?, purchaseprice = ? WHERE id = ?',
                array($product->availability, $product->purchasePrice, $articleDetailId)
            );

        } else {
            $this->manager->getConnection()->executeUpdate(
                'UPDATE s_articles_details SET instock = ? WHERE id = ?',
                array($product->availability, $articleDetailId)
            );
        }
        $this->manager->getConnection()->executeUpdate(
            "UPDATE s_articles_prices SET price = ?, baseprice = ? WHERE articledetailsID = ? AND pricegroup = 'EK'",
            array($product->price, $product->purchasePrice, $articleDetailId)
        );
    }

    public function changeAvailability($shopId, $sourceId, $availability)
    {
        // find article detail id
        $articleDetailId = $this->manager->getConnection()->fetchColumn(
            'SELECT article_detail_id FROM s_plugin_connect_items WHERE source_id = ? AND shop_id = ?',
            array($sourceId, $shopId)
        );

        // update stock in article detail
        $this->manager->getConnection()->executeUpdate(
            'UPDATE s_articles_details SET instock = ? WHERE id = ?',
            array($availability, $articleDetailId)
        );
    }

    public function makeMainVariant($shopId, $sourceId, $groupId)
    {
        // find article and detail id
        $result = $this->manager->getConnection()->fetchAssoc(
            'SELECT article_id, article_detail_id FROM s_plugin_connect_items WHERE source_id = ? AND shop_id = ?',
            array($sourceId, $shopId)
        );

        if (empty($result['article_detail_id']) || empty($result['article_id'])) {
            return;
        }

        $this->manager->getConnection()->executeUpdate(
            'UPDATE s_articles_details SET kind = IF(id = ?, 1, 2) WHERE articleID = ?',
            array($result['article_detail_id'], $result['article_id'])
        );

        $this->manager->getConnection()->executeUpdate(
            'UPDATE s_articles SET main_detail_id = ? WHERE id = ?',
            array($result['article_detail_id'], $result['article_id'])
        );
    }
}
