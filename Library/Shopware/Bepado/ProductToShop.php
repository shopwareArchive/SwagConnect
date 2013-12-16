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
use Shopware\Models\Attribute\Media as MediaAttribute;
use Shopware\Models\Media\Media as MediaModel;

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
        $attribute = $detail->getAttribute() ?: new AttributeModel();


        list($updateFields, $flag) = $this->getUpdateFields($model, $detail, $attribute, $product);
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
                $supplier = new \Shopware\Models\Article\Supplier();
                $supplier->setName($product->vendor);
            }
            $model->setSupplier($supplier);
        }

        if(($descField = $this->helper->getProductDescriptionField()) !== null) {
            $attribute->fromArray(array(
                $descField => $product->longDescription
            ));
        }

        $attribute->setBepadoShopId($product->shopId);
        $attribute->setBepadoSourceId($product->sourceId);
        $attribute->setBepadoExportStatus(null);
        $attribute->setBepadoCategories(serialize($product->categories));
        $attribute->setBepadoPurchasePrice($product->purchasePrice);
        $attribute->setBepadoFixedPrice($product->fixedPrice);
        $attribute->setBepadoFreeDelivery($product->freeDelivery);
        $attribute->setBepadoCategories(serialize($product->categories));
        $attribute->setBepadoLastUpdateFlag($flag);
        $detail->setInStock($product->availability);
        $model->setLastStock(true);

        // Whenever a product is updated, store a json encoded list of all fields that are updated optionally
        // This way a customer will be able to apply the most recent changes any time later
        $attribute->setBepadoLastUpdate(json_encode(array(
            'shortDescription'  => $product->shortDescription,
            'longDescription'   => $product->longDescription,
            'image'             => $product->images,
            'price'             => $product->price,
            'name'              => $product->title,
            'vat'               => $product->vat
        )));

        // Only set prices, if fixedPrice is active or price updates are configured
        if ($attribute->getBepadoFixedPrice() || $updateFields['price']) {
            $customerGroup = $this->helper->getDefaultCustomerGroup();

            $detail->getPrices()->clear();
            $price = new \Shopware\Models\Article\Price();
            $price->fromArray(array(
                'from' => 1,
                'price' => $product->price * 100 / (100 + 100 * $product->vat),
                'basePrice' => $attribute->getBepadoPurchasePrice() * 100 / (100 + 100 * $product->vat),
                'customerGroup' => $customerGroup,
                'article' => $model
            ));
            $detail->setPrices(array($price));
        }

        if($model->getMainDetail() === null) {
            $model->setMainDetail($detail);
            $this->manager->flush();
        }

        if($detail->getAttribute() === null) {
            $detail->setAttribute($attribute);
            $attribute->setArticle($model);
            $this->manager->flush();
        }

        $this->manager->flush();

        if ($updateFields['image']) {
            $this->handleImageImport($product->images, $model);
        }
    }


    /**
     * Handles the image import of a product. This will:
     * - delete all images imported from bepado before and not in the current import list
     * - create new images which have not already been imported
     * - set the main image, if there is no main image, yet
     *
     * Images are identified via the URL of the bepado image. So we don't need to md5 the
     * actual image content every time.
     *
     * @param array $images
     * @param $model
     */
    public function handleImageImport($images, $model)
    {
        // Build up an array of images imported from bepado
        $positions = array();
        $localImagesFromBepado = array();
        /** @var $image \Shopware\Models\Article\Image */
        /** @var $media \Shopware\Models\Media\Media */
        foreach ($model->getImages() as $image) {
            // Build a list of used position fields
            $position[] = $image->getPosition();

            $media = $image->getMedia();
            if (!$media || !$media->getAttribute()) {
                continue;
            }
            $attribute = $media->getAttribute();

            // If the image was not imported from bepado, skip it
            $bepadoHash = $attribute->getBepadoHash();
            if (!$bepadoHash) {
                continue;
            }

            $localImagesFromBepado[$bepadoHash] = array('image' => $image, 'media' => $media);
        }
        $maxPosition = max($positions); // Get the highest position field

        $remoteImagesFromBepado = array_flip($images);

        // Build up arrays of images to delete and images to create
        $imagesToDelete = array_diff_key($localImagesFromBepado, $remoteImagesFromBepado);
        $imagesToCreate = array_diff_key($remoteImagesFromBepado, $localImagesFromBepado);

        // Delete old bepado images and media objects
        foreach ($imagesToDelete as $hash => $data) {
            $this->manager->remove($data['image']);
            $this->manager->remove($data['media']);
        }
        $this->manager->flush();

        // Check if we still have a main image
        $hasMainImage = $this->helper->hasArticleMainImage($model->getId());

        // @todo:dn Move flushes out of the loop
        try {
            $album = $this->manager->find('Shopware\Models\Media\Album', -1);
            $tempDir = Shopware()->DocPath('media_temp');

            foreach ($imagesToCreate as $imageUrl => $key) {
                $tempFile = tempnam($tempDir, 'image');
                copy($imageUrl, $tempFile);
                $file = new \Symfony\Component\HttpFoundation\File\File($tempFile);

                // Create the media object
                $media = new MediaModel();
                $media->setAlbum($album);
                $media->setDescription('');
                $media->setCreated(new \DateTime());
                $media->setUserId(0);
                $media->setFile($file);

                $mediaAttribute = $media->getAttribute() ?: new MediaAttribute();
                $mediaAttribute->setBepadoHash($imageUrl);
                $mediaAttribute->setMedia($media);

                $this->manager->persist($media);
                $this->manager->persist($mediaAttribute);
                $this->manager->flush();

                // Create the associated image object
                $image = new \Shopware\Models\Article\Image();
                // If there is no main image and we are in the first iteration, set the current image as main image
                $image->setMain((!$hasMainImage && $key == 0) ? 1 : 2);
                $image->setMedia($media);
                $image->setPosition($maxPosition + $key + 1);
                $image->setArticle($model);
                $image->setPath($media->getName());
                $image->setExtension($media->getExtension());

                $this->manager->persist($image);
                $this->manager->flush();
            }
        } catch (\Exception $e) {
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
        if ($model->getAttribute()) {
            $model->getAttribute()->setBepadoExportStatus('delete');
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
        $fields = array(2 => 'shortDescription', 4 => 'longDescription', 8 => 'name', 16 => 'image', 32 => 'price');

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
     * @param $model
     * @param $attribute
     * @return bool|null
     * @throws \RuntimeException
     */
    public function isFieldUpdateAllowed($field, $model, $attribute)
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
        $attributeGetter = 'getBepadoUpdate' . $field;
        $configName = 'overwriteProduct' . $field;

        if (!in_array($field, $allowed)) {
            throw new \RuntimeException("Unknown field {$field}");
        }

        $attributeValue = $attribute->$attributeGetter();


        
        // If the value is 'null' or 'inherit', the behaviour will be inherited from the global configuration
        if ($attributeValue == null || $attributeValue == 'inherit') {
            return $this->config->get($configName, true);
        }

        return $attributeValue == 'overwrite';

    }
}
