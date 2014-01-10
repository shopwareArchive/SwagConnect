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

namespace Shopware\Bepado;
use Bepado\SDK\ProductToShop as ProductToShopBase,
    Bepado\SDK\Struct\Product,
    Shopware\Models\Article\Article as ProductModel,
    Shopware\Models\Article\Detail as DetailModel,
    Shopware\Models\Attribute\Article as AttributeModel,
    Shopware\Components\Model\ModelManager,
    Doctrine\ORM\Query;
use Shopware\CustomModels\Bepado\Attribute as BepadoAttribute;
use Shopware\Models\Article\Image;
use Shopware\Models\Article\Price;
use Shopware\Models\Article\Supplier;

/**
 * @category  Shopware
 * @package   Shopware\Plugins\SwagBepado
 * @copyright Copyright (c) 2013, shopware AG (http://www.shopware.de)
 * @author    Heiner Lohaus
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
     * @var \Shopware_Components_Config
     */
    private $config;

    /**
     * @param Helper $helper
     * @param ModelManager $manager
     * @param \Shopware_Components_Config $config
     */
    public function __construct(Helper $helper, ModelManager $manager,  $config)
    {
        $this->helper = $helper;
        $this->manager = $manager;
        $this->config = $config;
    }

    /**
     * Wrapping the insert method to catch errors during development.
     * @todo:dn Remove for closed beta
     *
     * @param Product $product
     * @throws \Exception
     */
    public function insertOrUpdate(Product $product)
    {
        try {
            $this->insertOrUpdateInternal($product);
        } catch(\Exception $e) {
            error_log(print_r($e->getMessage(), true)."\n", 3, Shopware()->DocPath().'/import_errors.log');
            error_log(print_r($e->getTraceAsString(), true)."\n\n", 3, Shopware()->DocPath().'/import_errors.log');
            throw $e;
        }
    }

    /**
     * Import or update given product
     *
     * Store product in your shop database as an external product. The
     * associated sourceId
     *
     * @param Product $product
     */
    public function insertOrUpdateInternal(Product $product)
    {
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
            $model->setCategories(
                $this->helper->getCategoriesByProduct($product)
            );
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

        if(($descField = $this->helper->getProductDescriptionField()) !== null) {
            $bepadoAttribute->fromArray(array(
                $descField => $product->longDescription
            ));
        }

        $bepadoAttribute->setShopId($product->shopId);
        $bepadoAttribute->setSourceId($product->sourceId);
        $bepadoAttribute->setExportStatus(null);
        $bepadoAttribute->setCategories(serialize($product->categories));
        $bepadoAttribute->setPurchasePrice($product->purchasePrice);
        $bepadoAttribute->setFixedPrice($product->fixedPrice);
        $bepadoAttribute->setFreeDelivery($product->freeDelivery);
        $bepadoAttribute->setCategories(serialize($product->categories));
        $bepadoAttribute->setLastUpdateFlag($flag);
        $detail->setInStock($product->availability);
        $model->setLastStock(true);

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
            $this->manager->flush();
        }

        if($detail->getAttribute() === null) {
            $detail->setAttribute($detailAttribute);
            $detailAttribute->setArticle($model);
            $this->manager->flush();
        }

        $bepadoAttribute->setArticle($model);
        $bepadoAttribute->setArticleDetail($detail);
        $this->manager->persist($bepadoAttribute);

        $this->manager->flush();

        if ($updateFields['image']) {
            $this->helper->handleImageImport($product->images, $model);
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
        //$model->getDetails()->clear();
        //$this->manager->remove($model);
        $model->setActive(false);

        // Not sure why, but the Attribute can be NULL
        $attribute = $this->helper->getBepadoAttributeByModel($model);
        if ($attribute) {
            $attribute->setExportStatus('delete');
        }
        $this->manager->flush($model);
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

        $flag = 0;
        $output = array();
        foreach ($fields as $key => $field) {
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
            return $this->config->get($configName, true);
        }

        return $attributeValue == 'overwrite';
    }
}
