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

namespace Shopware\Bepado\Components;
use Bepado\SDK\ProductToShop as ProductToShopBase,
    Bepado\SDK\Struct\Product,
    Shopware\Models\Article\Article as ProductModel,
    Shopware\Models\Article\Detail as DetailModel,
    Shopware\Models\Attribute\Article as AttributeModel,
    Shopware\Components\Model\ModelManager,
    Doctrine\ORM\Query;
use Shopware\Bepado\Components\Utils\UnitMapper;
use Shopware\CustomModels\Bepado\Attribute as BepadoAttribute;
use Shopware\Models\Article\Image;
use Shopware\Models\Article\Price;
use Shopware\Models\Article\Supplier;

/**
 * The interface for products imported *from* bepado *to* the local shop
 *
 * @category  Shopware
 * @package   Shopware\Plugins\SwagBepado
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
     * @var \Shopware\Bepado\Components\Config
     */
    private $config;

    /**
     * @var ImageImport
     */
    private $imageImport;

    /**
     * @param Helper $helper
     * @param ModelManager $manager
     * @param ImageImport $imageImport
     * @param \Shopware\Bepado\Components\Config $config
     */
    public function __construct(Helper $helper, ModelManager $manager, ImageImport $imageImport, $config)
    {
        $this->helper = $helper;
        $this->manager = $manager;
        $this->config = $config;
        $this->imageImport = $imageImport;
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
        if(empty($product->title) || empty($product->vendor)) {
            return;
        }
        $model = $this->helper->getArticleModelByProduct($product);

        if($model === null) {
            $model = new ProductModel();
            $model->setActive(false);
            $detail = new DetailModel();
            $detail->setNumber('BP-' . $product->shopId . '-' . $product->sourceId);
            $detail->setActive(false);
            $this->manager->persist($model);
            $detail->setArticle($model);

            $categories = $this->helper->getCategoriesByProduct($product);

            if (empty($categories)) {
                //add default import category
                $defaultCategoryId = $this->config->getConfig('defaultImportCategory');
                if ($defaultCategoryId) {
                    /** @var \Shopware\Models\Category\Category $defaultCategory */
                    $defaultCategory = $this->manager->getRepository('Shopware\Models\Category\Category')->find($defaultCategoryId);
                    if ($defaultCategory) {
                        $categories[] = $defaultCategory;
                    }
                }
            }

            $model->setCategories($categories);
        } else {
            $detail = $model->getMainDetail();
        }

        /*
         * We cannot import the freeDelivery property to the default product
         * as this might switch shopware shipping cost calculation for the local
         * shop into "shipping free" mode.
         */
        // $detail->setShippingFree($product->freeDelivery);
        $bepadoAttribute = $this->helper->getBepadoAttributeByModel($detail) ?: new BepadoAttribute;
        $detailAttribute = $detail->getAttribute() ?: new AttributeModel();

        list($updateFields, $flag) = $this->getUpdateFields($model, $detail, $bepadoAttribute, $product);
        /*
         * Make sure, that the following properties are set for
         * - new products
         * - products that have been configured to recieve these updates
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

        if($product->vat !== null) {
            $repo = $this->manager->getRepository('Shopware\Models\Tax\Tax');
            $tax = round($product->vat * 100, 2);
            $tax = $repo->findOneBy(array('tax' => $tax));
            $model->setTax($tax);
        }

        if($product->vendor !== null) {
            $repo = $this->manager->getRepository('Shopware\Models\Article\Supplier');
            $supplier = $repo->findOneBy(array('name' => $product->vendor));
            if($supplier === null) {
                $supplier = new Supplier();
                $supplier->setName($product->vendor);
            }
            $model->setSupplier($supplier);
        }

        // Set the configured attribute so users can easily check if a given product is a bepado attribute
        $setter = 'setAttr' . $this->config->getConfig('bepadoAttribute', 19);
        $detailAttribute->$setter($product->sourceId);

        $bepadoAttribute->setShopId($product->shopId);
        $bepadoAttribute->setSourceId($product->sourceId);
        $bepadoAttribute->setExportStatus(null);
        $bepadoAttribute->setPurchasePrice($product->purchasePrice);
        $bepadoAttribute->setFixedPrice($product->fixedPrice);
        $bepadoAttribute->setFreeDelivery($product->freeDelivery);
        $bepadoAttribute->setCategory($this->helper->getMostRelevantBepadoCategory($product->categories));
        $bepadoAttribute->setLastUpdateFlag($flag);
        $detail->setInStock($product->availability);
        $model->setLastStock(true);

        // if bepado product has unit
        // find local unit with units mapping
        // and add to detail model
        if ($product->attributes['unit']) {
            /** @var \Shopware\Bepado\Components\Config $configComponent */
            $configComponent = new Config($this->manager);

            /** @var \Shopware\Bepado\Components\Utils\UnitMapper $unitMapper */
            $unitMapper = new UnitMapper($configComponent, $this->manager);

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
        if ($product->attributes['dimension']) {
            $dimension = explode('x', $product->attributes['dimension']);
            $detail->setLen($dimension[0]);
            $detail->setWidth($dimension[1]);
            $detail->setHeight($dimension[2]);
        } else {
            $detail->setLen(null);
            $detail->setWidth(null);
            $detail->setHeight(null);
        }

        // Whenever a product is updated, store a json encoded list of all fields that are updated optionally
        // This way a customer will be able to apply the most recent changes any time later
        $bepadoAttribute->setLastUpdate(json_encode(array(
            'shortDescription'  => $product->shortDescription,
            'longDescription'   => $product->longDescription,
            'purchasePrice'     => $product->purchasePrice,
            'image'             => $product->images,
            'price'             => $product->price * ($product->vat + 1),
            'name'              => $product->title,
            'vat'               => $product->vat
        )));

        // The basePrice (purchasePrice) needs to be updated in any case
        $basePrice = $product->purchasePrice;

        // Only set prices, if fixedPrice is active or price updates are configured
        if (count($detail->getPrices()) == 0 || $bepadoAttribute->getFixedPrice() || $updateFields['price']) {
            $customerGroup = $this->helper->getDefaultCustomerGroup();

            $detail->getPrices()->clear();
            $price = new Price();
            $price->fromArray(array(
                'from' => 1,
                'price' => $product->price,
                'basePrice' => $basePrice,
                'customerGroup' => $customerGroup,
                'article' => $model
            ));
            $detail->setPrices(array($price));
        // If the price is not being update, update the basePrice anyway
        } else {
            /** @var \Shopware\Models\Article\Price $price */
            $price = $detail->getPrices()->first();
            $price->setBasePrice($basePrice);
        }

        if($model->getMainDetail() === null) {
            $model->setMainDetail($detail);
        }

        if($detail->getAttribute() === null) {
            $detail->setAttribute($detailAttribute);
            $detailAttribute->setArticle($model);
        }

        $bepadoAttribute->setArticle($model);
        $bepadoAttribute->setArticleDetail($detail);
        $this->manager->persist($bepadoAttribute);

        $this->manager->flush();
        $this->manager->clear();

        //clear cache for that article
        $this->helper->clearArticleCache($model->getId());

        if ($updateFields['image']) {
            // Reload the model in order to not to work an the already flushed model
            $model = $this->helper->getArticleModelByProduct($product);
            $this->imageImport->importImagesForArticle($product->images, $model);
        }

    }


    /**
     * Delete product with given shopId and sourceId.
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
        $model =  $this->helper->getArticleModelByProduct(new Product(array(
            'shopId' => $shopId,
            'sourceId' => $sourceId,
        )));
        if($model === null) {
            return;
        }

        // Not sure why, but the Attribute can be NULL
        $attribute = $this->helper->getBepadoAttributeByModel($model);
        if ($attribute) {
            $this->manager->remove($attribute);
        }
        $model->getDetails()->clear();

        $this->manager->remove($model);

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
    }

    /**
     * Helper method to determine if a given $fields may/must be updated.
     * This method will check for the model->id in order to determine, if it is a new entity. Therefore
     * this method cannot be used after the model in question was already flushed.
     *
     * @param $field
     * @param $model ProductModel
     * @param $attribute BepadoAttribute
     * @return bool|null
     * @throws \RuntimeException
     */
    public function isFieldUpdateAllowed($field, ProductModel $model, BepadoAttribute $attribute)
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

}
