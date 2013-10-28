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
     * @param Helper $helper
     * @param ModelManager $manager
     */
    public function __construct(Helper $helper, ModelManager $manager)
    {
        $this->helper = $helper;
        $this->manager = $manager;
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
            $model->setName($product->title);
            $model->setDescription($product->shortDescription);
            $model->setDescriptionLong($product->longDescription);
        } else {
            $detail = $model->getMainDetail();
        }

        $attribute = $detail->getAttribute() ?: new AttributeModel();

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
        $attribute->setBepadoCategories(serialize($product->categories));
        $detail->setInStock($product->availability);
        $model->setLastStock(true);

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

        // @fixme WORKAROUND: there can always be an image update
        foreach ($model->getImages() as $image) {
            if (file_exists($image->getPath())) {
                unlink($image->getPath());
            }

            $this->manager->remove($image);
            $this->manager->flush();
        }
        //if($model->getImages()->count() != 0)  {
        //    return;
        //}

        try {
            $album = $this->manager->find('Shopware\Models\Media\Album', -1);
            $tempDir = Shopware()->DocPath('media_temp');
            foreach ($product->images as $key => $imageUrl) {
                //$name = pathinfo($imageUrl, PATHINFO_FILENAME);
                //$name = md5(uniqid('', true));
                $tempFile = tempnam($tempDir, 'image');
                copy($imageUrl, $tempFile);
                $file = new \Symfony\Component\HttpFoundation\File\File($tempFile);

                $media = new \Shopware\Models\Media\Media();
                $media->setAlbum($album);
                //$media->setName($name);
                $media->setDescription('');
                $media->setCreated(new \DateTime());
                $media->setUserId(0);
                $media->setFile($file);

                $this->manager->persist($media);
                $this->manager->flush();

                $image = new \Shopware\Models\Article\Image();
                $image->setMain($key == 0 ? 1 : 2);
                $image->setMedia($media);
                $image->setPosition($key + 1);
                $image->setArticle($model);
                $image->setPath($media->getName());
                $image->setExtension($media->getExtension());

                $this->manager->persist($image);
                $this->manager->flush();

            }
        } catch(\Exception $e) { }
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
}
