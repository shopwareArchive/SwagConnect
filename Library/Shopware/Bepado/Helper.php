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
use Doctrine\ORM\QueryBuilder;
use Exception;
use Bepado\SDK\SDK;
use Bepado\SDK\Struct\Product,
    Shopware\Models\Article\Article as ProductModel,
    Shopware\Models\Category\Category as CategoryModel,
    Shopware\Components\Model\ModelManager,
    Doctrine\ORM\Query;
use Shopware\CustomModels\Bepado\Attribute as BepadoAttribute;
use Shopware\Models\Article\Detail as ProductDetail;
use Shopware\Models\Media\Media as MediaModel;
use Shopware\Models\Attribute\Media as MediaAttribute;
use Shopware\Models\Article\Image;

/**
 * @category  Shopware
 * @package   Shopware\Plugins\SwagBepado
 */
class Helper
{
    /**
     * @var ModelManager
     */
    private $manager;

    /**
     * @var string
     */
    private $imagePath;

    /**
     * @var Query
     */
    private $modelQuery, $productQuery, $categoryQuery, $productCategoryQuery;

    private $bepadoCategoryQuery;

    /** @var \Shopware\Bepado\ProductQuery  */
    private $bepadoProductQuery;

    /**
     * @param ModelManager $manager
     * @param string $imagePath
     * @param CategoryQuery
     * @param ProductQuery
     */
    public function __construct(
        ModelManager $manager,
        $imagePath,
        CategoryQuery $bepadoCategoryQuery,
        ProductQuery $bepadoProductQuery
    )
    {
        $this->manager = $manager;
        $this->imagePath = $imagePath;
        $this->bepadoCategoryQuery = $bepadoCategoryQuery;
        $this->bepadoProductQuery = $bepadoProductQuery;
    }

    /**
     * @return \Shopware\Models\Article\Repository
     */
    private function getArticleRepository()
    {
        $repository = $this->manager->getRepository(
            'Shopware\Models\Article\Article'
        );
        return $repository;
    }

    /**
     * @return \Shopware\Models\Category\Repository
     */
    private function getCategoryRepository()
    {
        $repository = $this->manager->getRepository(
            'Shopware\Models\Category\Category'
        );
        return $repository;
    }

    /**
     * @return \Shopware\Models\Customer\Group
     */
    public function getDefaultCustomerGroup()
    {
        $repository = $this->manager->getRepository('Shopware\Models\Customer\Group');
        $customerGroup = $repository->findOneBy(array('key' => 'EK'));
        return $customerGroup;
    }

    /**
     * @return null|string
     */
    public function getProductDescriptionField()
    {
        return $this->bepadoProductQuery->getProductDescriptionField();
    }

    /**
     * @return Query
     */
    private function getProductQuery()
    {
        if (!$this->productQuery) {
            $this->productQuery = $this->bepadoProductQuery->getProductQuery();
        }
        return $this->productQuery;
    }

    /**
     * @return QueryBuilder
     */
    private function getArticleModelQueryBuilder()
    {
        $repository = $this->getArticleRepository();
        $builder = $repository->createQueryBuilder('a');
        $builder->select(array('a', 'd', 'at'));
        $builder->join('a.mainDetail', 'd');
        $builder->leftJoin('d.attribute', 'at');
        return $builder;
    }

    /**
     * @return \Doctrine\ORM\Query
     */
    private function getArticleModelByIdQuery()
    {
        $builder = $this->getArticleModelQueryBuilder();
        $builder->where('a.id = :id');
        $query = $builder->getQuery();
        $query->setHydrationMode($query::HYDRATE_OBJECT);
        return $query;
    }

    /**
     * @param $id
     * @return null|ProductModel
     */
    public function getArticleModelById($id)
    {
        if($this->modelQuery === null) {
            $this->modelQuery = $this->getArticleModelByIdQuery();
        }
        $result = $this->modelQuery->setParameter('id', $id)->execute();
        return isset($result[0]) ? $result[0] : null;
    }

    /**
     * @return \Doctrine\ORM\Query
     */
    private function getCategoryModelByIdQuery()
    {
        $repository = $this->getCategoryRepository();
        $builder = $repository->createQueryBuilder('c');
        $builder->join('c.attribute', 'ct');
        $builder->addSelect('ct');
        $builder->where('c.id = :id');
        $query = $builder->getQuery();
        $query->setHydrationMode($query::HYDRATE_OBJECT);
        return $query;
    }

    /**
     * @param $id
     * @return null|\Shopware\Models\Category\Category
     */
    public function getCategoryModelById($id)
    {
        if($this->categoryQuery === null) {
            $this->categoryQuery = $this->getCategoryModelByIdQuery();
        }
        $result = $this->categoryQuery->setParameter('id', $id)->execute();
        return isset($result[0]) ? $result[0] : null;
    }

    /**
     * @param array $row
     * @return Product
     */
    public function getProductByRowData($row)
    {
        if(isset($row['deliveryDate'])) {
            $row['deliveryDate'] = $row['deliveryDate']->getTimestamp();
        }

        if(!empty($row['altDescription'])) {
            $row['longDescription'] = $row['altDescription'];
        }
        unset($row['altDescription']);

        // Fix categories
        if(is_string($row['categories'])) {
            $row['categories'] = unserialize($row['categories']);
        }

        // If the product is a remote product, the freeDelivery needs to be replaced
        // Else we need some price replacement
        if (!empty($row['shopId'])) {
            $row['freeDelivery'] = $row['bepadoFreeDelivery'];
        }else{
            if ($row['bepadoExportPrice']) {
                $row['price'] = $row['bepadoExportPrice'];
            }

            if ($row['bepadoExportPurchasePrice']) {
                $row['purchasePrice'] = $row['bepadoExportPurchasePrice'];
            }
        }
        unset($row['bepadoFreeDelivery']);
        unset($row['bepadoExportPrice']);
        unset($row['bepadoExportPurchasePrice']);

        // Fix prices
        foreach(array('price', 'purchasePrice', 'vat') as $name) {
            $row[$name] = round($row[$name], 2);
        }

        // Fix attributes
        foreach(array('weight', 'unit', 'base', 'volume') as $name) {
            if(isset($row[$name])) {
                $row['attributes'][$name] = $row[$name];
            }
            unset($row[$name]);
        }

        // Fix dimensions
        if(!empty($row['width']) && !empty($row['height'])) {
            $dimension = array(
                $row['width'], $row['height']
            );
            if(!empty($row['length'])) {
                $dimension[] = $row['length'];
            }
            $row['attributes'][Product::ATTRIBUTE_DIMENSION] = implode('x', $dimension);
        }
        unset($row['width'], $row['height'], $row['length']);

        $product = new Product(
            $row
        );
        return $product;
    }

    /**
     * Returns an article model for a given (sdk) product.
     *
     * @param Product $product
     * @param int $mode
     * @return null|ProductModel
     */
    public function getArticleModelByProduct(Product $product, $mode = Query::HYDRATE_OBJECT)
    {
        $builder = $this->manager->createQueryBuilder();
        $builder->select(array('ba', 'a'));
        $builder->from('Shopware\CustomModels\Bepado\Attribute', 'ba');
        $builder->join('ba.article', 'a');
        $builder->join('a.mainDetail', 'd');
        $builder->leftJoin('d.attribute', 'at');

        $builder->where('ba.shopId = :shopId AND ba.sourceId = :sourceId');
        $builder->orWhere('d.number = :number');
        $query = $builder->getQuery();

        $query->setParameter('shopId', $product->shopId);
        $query->setParameter('sourceId', $product->sourceId);
        $query->setParameter('number', 'BP-' . $product->shopId . '-' . $product->sourceId);
        $result = $query->getResult(
            $query::HYDRATE_OBJECT,
            $mode
        );

        if (isset($result[0])) {
            $attribute = $result[0];
            return $attribute->getArticle();
        }

        return null;
    }

    /**
     * Helper to update the bepado_items table
     */
    public function updateBepadoProducts()
    {
        // Insert new articles
        $sql = '
        INSERT INTO `s_plugin_bepado_items` (article_id, article_detail_id)
        SELECT articleID, articledetailsID

        FROM s_articles_attributes aa

        LEFT JOIN `s_plugin_bepado_items` bi
        ON bi.article_detail_id = aa.articledetailsid
        AND bi.article_id = aa.articleID

        WHERE aa.articleID IS NOT NULL
        AND aa.articledetailsID IS NOT NULL
        AND bi.id IS NULL
        ';

        $this->manager->getConnection()->exec($sql);

        // Delete removed articles from s_plugin_bepado_items
        $sql = '
        DELETE bi FROM `s_plugin_bepado_items`  bi

        LEFT JOIN `s_articles_attributes` aa
        ON aa.articledetailsID = bi.article_detail_id

        WHERE aa.articleID IS NULL
        ';

        $this->manager->getConnection()->exec($sql);

    }

    /**
     * @param $id
     * @return null|array
     */
    public function getRowProductDataById($id)
    {
        $result = $this->getProductQuery()->setParameter(
            'id', $id
        )->execute();

        if(!isset($result[0])) {
            return null;
        }
        return $result[0];
    }

    /**
     * @param $id
     * @return \Bepado\SDK\Struct\Product
     */
    public function getProductById($id)
    {
        $data = $this->getRowProductDataById($id);
        $data['images'] = $this->getImagesById($id);
        return $this->getProductByRowData($data) ;
    }

    /**
     * Does the current basket contain bepado products?
     *
     * @param $session
     * @return bool
     */
    public function hasBasketBepadoProducts($session)
    {
        $connection = $this->manager->getConnection();
        $result = $connection->fetchArray(
            'SELECT ob.articleID

            FROM s_order_basket ob

            INNER JOIN s_plugin_bepado_items bi
            ON bi.article_id = ob.articleID
            AND bi.shop_id IS NOT NULL

            WHERE ob.sessionID=?
            LIMIT 1
            ',
            array($session)
        );

        return !empty($result);
    }

    /**
     * Will return the bepadoAttribute for a given model. The model can be an Article\Article or Article\Detail
     *
     * @param $model ProductModel|ProductDetail
     * @return BepadoAttribute
     */
    public function getBepadoAttributeByModel($model)
    {
        $repository = $this->manager->getRepository('Shopware\CustomModels\Bepado\Attribute');

        if (!$model->getId()) {
            return false;
        }

        if ($model instanceof ProductModel) {
            if (!$model->getMainDetail()) {
                return false;
            }
            return $repository->findOneBy(array('articleDetailId' => $model->getMainDetail()->getId()));
        } elseif ($model instanceof ProductDetail) {
            return $repository->findOneBy(array('articleDetailId' => $model->getId()));
        }
    }

    /**
     * Helper method to create a bepado attribute on the fly
     *
     * @param $model
     * @return BepadoAttribute
     * @throws \RuntimeException
     */
    public function getOrCreateBepadoAttributeByModel($model)
    {
        $attribute = $this->getBepadoAttributeByModel($model);

        if (!$attribute) {
            $attribute = new BepadoAttribute();
            if ($model instanceof ProductModel) {
                $attribute->setArticle($model);
                $attribute->setArticleDetail($model->getMainDetail());
            } elseif ($model instanceof ProductDetail) {
                $attribute->setArticle($model->getArticle());
                $attribute->setArticleDetail($model);
            } else {
                throw new \RuntimeException("Passed model needs to be an article or an article detail");
            }
            $this->manager->persist($attribute);
            $this->manager->flush($attribute);
        }

        return $attribute;
    }

    /**
     * @param $id
     * @return string[]
     */
    public function getImagesById($id)
    {
        if($this->imagePath === null) {
            return array();
        }

        $builder = $this->manager->createQueryBuilder();
        $builder->select(array('i.path', 'i.extension', 'i.main', 'i.position'))
            ->from('Shopware\Models\Article\Image', 'i')
            ->where('i.articleId = :articleId')
            ->andWhere('i.parentId IS NULL')
            ->setParameter('articleId', $id)
            ->orderBy('i.main', 'ASC')
            ->addOrderBy('i.position', 'ASC');

        $query = $builder->getQuery();
        $query->setHydrationMode($query::HYDRATE_OBJECT);

        $images = $query->getArrayResult();

        $imagePath = $this->imagePath;
        $images = array_map(function($image) use ($imagePath) {
            return "{$imagePath}{$image['path']}.{$image['extension']}";
        }, $images);

        return $images;
    }

    /**
     * @param $id
     * @return null|array
     */
    public function getRowProductCategoriesById($id)
    {
        return $this->getCategoryQuery()->getRowProductCategoriesById($id);
    }

    /**
     * @param Product $product
     * @return \Shopware\Models\Category\Category[]
     */
    public function getCategoriesByProduct(Product $product)
    {
        return $this->getCategoryQuery()->getCategoriesByProduct($product);
    }

    protected function getCategoryQuery()
    {
        return $this->bepadoCategoryQuery;
    }

    /**
     * Helper to determine, if there is a main image for a given articleId
     *
     * @param $articleId
     * @return bool
     */
    public function hasArticleMainImage($articleId)
    {
        $builder = $this->manager->createQueryBuilder();
        $builder->select(array('images'))
            ->from('Shopware\Models\Article\Image', 'images')
            ->where('images.articleId = :articleId')
            ->andWhere('images.parentId IS NULL')
            ->andWhere('images.main = :main')
            ->setParameter('main', 1)
            ->setParameter('articleId', $articleId)
            ->setFirstResult(0)
            ->setMaxResults(1);

        $result = $builder->getQuery()->getResult();
        return !empty($result);
    }

    /**
     * Get ids of products that needs an image import
     * @param null $limit
     * @return array        Ids of products needing an image import
     */
    public function getProductsNeedingImageImport($limit=null)
    {
        $updateFlags = $this->getUpdateFlags();
        $updateFlagsByName = array_flip($updateFlags);

        $initialImportFlag = $updateFlagsByName['imageInitialImport'];

        $builder = $this->manager->createQueryBuilder();
            $builder->from('Shopware\CustomModels\Bepado\Attribute', 'at');
            $builder->innerJoin('at.articleDetail', 'detail');
            $builder->select('at.articleId');
            $builder->andWhere('at.shopId IS NOT NULL')
            ->andWHere('at.lastUpdateFlag IS NOT NULL')
            ->andWHere('BIT_AND(at.lastUpdateFlag, :initialImportFlag) > 0')
            ->setParameter('initialImportFlag', $initialImportFlag);

        if ($limit) {
            $builder->setMaxResults($limit);
        }

        $ids = $builder->getQuery()->getArrayResult();
        return array_map('array_pop', $ids);

    }

    /**
     * Batch import images for new products without images
     *
     * @param null $limit
     */
    public function importImages($limit=null)
    {
        $articleRepository = $this->manager->getRepository('Shopware\Models\Article\Article');

        $flags = $this->getUpdateFlags();
        $flagsByName = array_flip($flags);

        $ids = $this->getProductsNeedingImageImport($limit);

        foreach ($ids as $id) {
            /** @var \Shopware\Models\Article\Article $model */
            $model = $articleRepository->find($id);
            $bepadoAttribute = $this->getBepadoAttributeByModel($model);

            $lastUpdate = json_decode($bepadoAttribute->getLastUpdate(), true);

            $this->handleImageImport($lastUpdate['image'], $model);

            $bepadoAttribute->flipLastUpdateFlag($flagsByName['imageInitialImport']);

            $this->manager->flush();
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
     * @param $model ProductModel
     */
    public function handleImageImport($images, $model)
    {
        // Build up an array of images imported from bepado
        $positions = array();
        $localImagesFromBepado = array();

        /** @var $image Image */
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
        $hasMainImage = $this->hasArticleMainImage($model->getId());

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
                $image = new Image();
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
     * Defines the update flags
     *
     * @return array
     */
    public function getUpdateFlags()
    {
        return array(2 => 'shortDescription', 4 => 'longDescription', 8 => 'name', 16 => 'image', 32 => 'price', 64 => 'imageInitialImport');
    }

    /**
     * Helper function to mark a given array of product ids for bepado update
     *
     * @param array $ids
     * @param $sdk SDK
     * @return array
     */
    public function insertOrUpdateProduct(array $ids, $sdk)
    {

        $errors = array();

        foreach($ids as $id) {
            $model = $this->getArticleModelById($id);
            $prefix = $model && $model->getName() ? $model->getName() . ': ' : '';

            if($model === null) {
                continue;
            }
            $bepadoAttribute = $this->getBepadoAttributeByModel($model) ?: new BepadoAttribute;

            $status = $bepadoAttribute->getExportStatus();
            if(empty($status) || $status == 'delete' || $status == 'error') {
                $status = 'insert';
            } else {
                $status = 'update';
            }
            $bepadoAttribute->setExportStatus(
                $status
            );
            $bepadoAttribute->setExportMessage(null);

            $categories = $this->getRowProductCategoriesById($id);
            // Don't allow vendor categories for export
            $filteredCategories = array_filter($categories, function($category) {
                return strpos($category, '/vendor/') !== 0 && $category != '/vendor';
            });
            $bepadoAttribute->setCategories(
                serialize($filteredCategories)
            );

            if (empty($filteredCategories) && count($filteredCategories) != count($categories)) {
                $removed = implode("\n", array_diff($categories, $filteredCategories));
                $errors[] = " &bull; {$prefix} Ignoring these vendor-categories {$model->getName()}: {$removed}";
            }

            if (!$bepadoAttribute->getId()) {
                $this->manager->persist($bepadoAttribute);
            }
            $this->manager->flush($bepadoAttribute);
            try {
                if($status == 'insert') {
                    $sdk->recordInsert($id);
                } else {
                    $sdk->recordUpdate($id);
                }
            } catch(Exception $e) {
                $bepadoAttribute->setExportStatus(
                    'error'
                );
                $bepadoAttribute->setExportMessage(
                    $e->getMessage() . "\n" . $e->getTraceAsString()
                );

                $errors[] = " &bull; " . $prefix . $e->getMessage();
                $this->manager->flush($bepadoAttribute);
            }
        }

        return $errors;
    }
}
