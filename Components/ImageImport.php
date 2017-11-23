<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Components;

use Shopware\Components\Model\ModelManager;
use Shopware\Models\Article\Article;
use Shopware\Models\Article\Detail;
use Shopware\Models\Article\Image;
use Shopware\Models\Media\Media;
use Shopware\Models\Attribute\Media as MediaAttribute;
use Shopware\Models\Attribute\ArticleImage as ImageAttribute;
use Symfony\Component\HttpFoundation\File\File;
use Shopware\Models\Article\Supplier;
use Shopware\Components\Thumbnail\Manager as ThumbnailManager;

class ImageImport
{
    /** @var \Shopware\Components\Model\ModelManager */
    protected $manager;

    /** @var Helper */
    protected $helper;

    /**
     * @var ThumbnailManager
     */
    protected $thumbnailManager;

    /** @var \ShopwarePlugins\Connect\Components\Logger */
    protected $logger;

    public function __construct(
        ModelManager $manager,
        Helper $helper,
        ThumbnailManager $thumbnailManager,
        Logger $logger
    ) {
        $this->manager = $manager;
        $this->helper = $helper;
        $this->thumbnailManager = $thumbnailManager;
        $this->logger = $logger;
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
        $builder->select(['images'])
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
        $updateFlags = $this->helper->getUpdateFlags();
        $updateFlagsByName = array_flip($updateFlags);

        $initialImportFlag = $updateFlagsByName['imageInitialImport'];

        $builder = $this->manager->createQueryBuilder();
        $builder->from('Shopware\CustomModels\Connect\Attribute', 'at');
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
     * @param int|null $limit
     */
    public function import($limit = null)
    {
        $articleRepository = $this->manager->getRepository('Shopware\Models\Article\Article');

        $flags = $this->helper->getUpdateFlags();
        $flagsByName = array_flip($flags);

        $ids = $this->getProductsNeedingImageImport($limit);

        foreach ($ids as $id) {
            /** @var \Shopware\Models\Article\Article $article */
            $article = $articleRepository->find($id);
            $connectAttributes = $this->helper->getConnectAttributesByArticle($article);

            /** @var \Shopware\CustomModels\Connect\Attribute $connectAttribute */
            foreach ($connectAttributes as $connectAttribute) {
                $lastUpdate = json_decode($connectAttribute->getLastUpdate(), true);
                // update mainImage can be set to true because this just runs on initial import
                $this->importImagesForArticle(array_diff($lastUpdate['image'], $lastUpdate['variantImages']), $article);
                $this->importImagesForDetail($lastUpdate['variantImages'], $connectAttribute->getArticleDetail());
                $connectAttribute->flipLastUpdateFlag($flagsByName['imageInitialImport']);
            }

            $this->manager->flush();
        }
    }

    /**
     * Handles the image import of a product. This will:
     * - delete all images imported from connect before and not in the current import list
     * - create new images which have not already been imported
     * - set the main image, if there is no main image, yet
     *
     * Images are identified via the URL of the connect image. So we don't need to md5 the
     * actual image content every time.
     *
     * @param array $images
     * @param $article \Shopware\Models\Article\Article
     */
    public function importImagesForArticle($images, Article $article)
    {
        $localImagesFromConnect = $this->getImportedImages($article);
        $remoteImagesFromConnect = array_flip($images);

        // Build up arrays of images to delete and images to create
        $imagesToDelete = array_diff_key($localImagesFromConnect, $remoteImagesFromConnect);
        $imagesToCreate = array_diff_key($remoteImagesFromConnect, $localImagesFromConnect);

        // Delete old connect images and media objects
        foreach ($imagesToDelete as $hash => $data) {
            /** @var \Shopware\Models\Article\Image $image */
            $image = $data['image'];
            // if the image has mapping, it's variant image and shouldn't be deleted
            if (count($image->getMappings()) > 0) {
                continue;
            }
            $this->manager->remove($image);
            $this->manager->remove($data['media']);
        }
        $this->manager->flush();

        try {
            $this->importImages($imagesToCreate, $article);
        } catch (\Exception $e) {
            // log exception message if for some reason
            // image import fails
            $this->logger->write(true, 'Import images', $e->getMessage());
        }

        $this->manager->flush();
    }

    /**
     * Handles the image import of a product. This will:
     * - delete all images imported from connect before and not in the current import list
     * - create new images which have not already been imported
     * - set the main image, if there is no main image, yet
     *
     * Images are identified via the URL of the connect image. So we don't need to md5 the
     * actual image content every time.
     *
     * @param array $variantImages
     * @param $detail \Shopware\Models\Article\Detail
     */
    public function importImagesForDetail(array $variantImages, Detail $detail)
    {
        $article = $detail->getArticle();
        $articleImages = $article->getImages();

        $localImagesFromConnect = $this->getImportedImages($detail);
        $localArticleImagesFromConnect = $this->getImportedImages($article);

        $remoteImagesFromConnect = array_flip($variantImages);

        // Build up arrays of images to delete and images to create
        $imagesToDelete = array_diff_key($localImagesFromConnect, $remoteImagesFromConnect);
        $imagesToCreate = array_diff_key($remoteImagesFromConnect, $localImagesFromConnect);

        $mappingRepository = $this->manager->getRepository('Shopware\Models\Article\Image\Mapping');
        // Delete old connect images and media objects
        foreach ($imagesToDelete as $hash => $data) {
            /** @var \Shopware\Models\Article\Image $image */
            $image = $data['image'];
            /** @var \Shopware\Models\Article\Image $child */
            foreach ($image->getChildren() as $child) {
                if ($detail->getId() == $child->getArticleDetail()->getId()) {
                    $childAttribute = $child->getAttribute();
                    if (!$childAttribute) {
                        break;
                    }

                    $mapping = $mappingRepository->find($childAttribute->getConnectDetailMappingId());
                    if (!$mapping) {
                        break;
                    }

                    $this->manager->remove($mapping);
                    $this->manager->remove($child);
                    break;
                }
            }

            if (count($image->getChildren()) == 1) {
                $this->manager->remove($image);
                $this->manager->remove($data['media']);
            }
        }
        $this->manager->flush();

        try {
            $positions = [];
            foreach ($article->getImages() as $image) {
                $positions[] = $image->getPosition();
            }
            $maxPosition = count($positions) > 0 ? max($positions) : 0;

            /** @var \Shopware\Models\Media\Album $album */
            $album = $this->manager->find('Shopware\Models\Media\Album', -1);
            foreach ($imagesToCreate as $imageUrl => $key) {
                // check if image already exists in article images
                // 1) if it exists skip import and it's global image
                if (array_key_exists($imageUrl, $localArticleImagesFromConnect)
                    && empty($localArticleImagesFromConnect[$imageUrl]['image']->getMappings())) {
                    // if image doesn't have mappings
                    // it's global for all details
                    // do nothing, just continue
                    continue;
                }

                // 2) if it has mapping, add new one for current detail
                if (array_key_exists($imageUrl, $localArticleImagesFromConnect)) {
                    /** @var \Shopware\Models\Article\Image $articleImage */
                    $articleImage = $localArticleImagesFromConnect[$imageUrl]['image'];
                    $articleMedia = $localArticleImagesFromConnect[$imageUrl]['media'];

                    // add new mapping
                    $mapping = new Image\Mapping();
                    $mapping->setImage($articleImage);
                    foreach ($detail->getConfiguratorOptions() as $option) {
                        $rule = new Image\Rule();
                        $rule->setMapping($mapping);
                        $rule->setOption($option);
                        $mapping->getRules()->add($rule);
                    }
                    $this->manager->persist($mapping);
                    // mapping should have id, because it should be stored as child image attribute
                    $this->manager->flush($mapping);
                    $articleImage->getMappings()->add($mapping);

                    // add child image
                    $childImage = new Image();
                    $childImage->setMain(2);
                    $childImage->setPosition($maxPosition + $key + 1);
                    $childImage->setParent($articleImage);
                    $childImage->setArticleDetail($detail);
                    $childImage->setExtension($articleMedia->getExtension());
                    $childImageAttribute = $childImage->getAttribute() ?: new ImageAttribute();
                    $childImageAttribute->setArticleImage($childImage);
                    $childImageAttribute->setConnectDetailMappingId($mapping->getId());

                    $detail->getImages()->add($childImage);
                    $articleImage->getChildren()->add($childImage);

                    $this->manager->persist($childImage);
                    $this->manager->persist($childImageAttribute);
                    $this->manager->persist($articleImage);

                    continue;
                }

                // 3) if it doesn't exist, import it
                $importedImages = $this->importImages([$imageUrl => $key], $article, $maxPosition);
                $image = reset($importedImages);
                $media = $image->getMedia();

                // add new mapping
                $mapping = new Image\Mapping();
                $mapping->setImage($image);
                foreach ($detail->getConfiguratorOptions() as $option) {
                    $rule = new Image\Rule();
                    $rule->setMapping($mapping);
                    $rule->setOption($option);
                    $rules = $mapping->getRules();
                    $rules->add($rule);
                    $mapping->setRules($rules);
                    $this->manager->persist($rule);
                }
                $this->manager->persist($mapping);
                // mapping should have id, because it should be stored as child image attribute
                $this->manager->flush($mapping);

                $mappings = $image->getMappings();
                $mappings->add($mapping);
                $image->setMappings($mappings);

                // add child image
                $childImage = new Image();
                $childImage->setMain(2);
                $childImage->setPosition($maxPosition + $key + 1);
                $childImage->setParent($image);
                $childImage->setArticleDetail($detail);
                $childImage->setExtension($media->getExtension());
                $childImageAttribute = $childImage->getAttribute() ?: new ImageAttribute();
                $childImageAttribute->setArticleImage($childImage);
                $childImageAttribute->setConnectDetailMappingId($mapping->getId());
                $detail->getImages()->add($childImage);

                $image->getChildren()->add($childImage);

                $this->manager->persist($childImage);
                $this->manager->persist($childImageAttribute);
                $this->manager->persist($image);

                $this->thumbnailManager->createMediaThumbnail(
                    $media,
                    $this->getThumbnailSize($album),
                    true
                );
            }
        } catch (\Exception $e) {
            // log exception message if for some reason
            // image import fails
            $this->logger->write(true, 'Import images', $e->getMessage());
        }

        $article->setImages($articleImages);
        $this->manager->persist($article);
        $this->manager->flush();
    }

    /**
     * Helper: Read images for a given detail
     *
     * @param int $articleDetailId
     * @return array
     */
    public function getImagesForDetail($articleDetailId)
    {
        $builder = $this->manager->createQueryBuilder();
        $builder->select('media.path')
            ->from('Shopware\Models\Article\Image', 'images')
            ->join('images.media', 'media')
            ->where('images.articleDetailId = :articleDetailId')
            ->andWhere('images.parentId IS NULL')
            ->setParameter('articleDetailId', $articleDetailId)
            ->orderBy('images.main', 'ASC')
            ->addOrderBy('images.position', 'ASC');

        return array_map(function ($image) {
            return $image['path'];
        },
            $builder->getQuery()->getArrayResult()
        );
    }

    /**
     * Download, import and assign images to article
     *
     * @param array $imagesToCreate
     * @param Article|Detail $model
     * @param null|int $maxPosition
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     * @throws \Exception
     * @return \Shopware\Models\Article\Image
     */
    private function importImages(array $imagesToCreate, $model, $maxPosition = null)
    {
        if (!$maxPosition) {
            $positions = [];
            foreach ($model->getImages() as $image) {
                $positions[] = $image->getPosition();
            }
            $maxPosition = count($positions) > 0 ? max($positions) : 0;
        }

        if ($model instanceof Detail) {
            $article = $model->getArticle();
        } elseif ($model instanceof Article) {
            $article = $model;
        } else {
            throw new \RuntimeException('Model must be instance of Article or Detail!');
        }

        // If there is no main image set first image as main image
        $hasMainImage = $this->hasArticleMainImage($article->getId());
        $importedImages = [];
        /** @var \Shopware\Models\Media\Album $album */
        $album = $this->manager->find('Shopware\Models\Media\Album', -1);
        $tempDir = Shopware()->DocPath('media_temp');
        foreach ($imagesToCreate as $imageUrl => $key) {
            $tempFile = tempnam($tempDir, 'image');
            copy($imageUrl, $tempFile);
            $file = new File($tempFile);

            // Create the media object
            $media = new Media();
            $media->setAlbum($album);
            $media->setDescription('');
            $media->setCreated(new \DateTime());
            $media->setUserId(0);
            $media->setFile($file);

            $mediaAttribute = $media->getAttribute() ?: new MediaAttribute();
            $mediaAttribute->setConnectHash($imageUrl);
            $mediaAttribute->setMedia($media);
            $media->setAttribute($mediaAttribute);
            $this->manager->persist($media);
            $this->manager->persist($mediaAttribute);

            // Create the associated image object
            $image = new Image();
            $image->setMain((!$hasMainImage && $key == 0) ? 1 : 2);
            $image->setMedia($media);
            $image->setPosition($maxPosition + $key + 1);
            $image->setArticle($article);
            $image->setPath($media->getName());
            $image->setExtension($media->getExtension());

            $article->getImages()->add($image);

            $this->manager->persist($image);

            $this->thumbnailManager->createMediaThumbnail(
                $media,
                $this->getThumbnailSize($album),
                true
            );

            $importedImages[] = $image;
        }

        return $importedImages;
    }

    /**
     * Returns a list with already imported images from Connect
     * by given Article or Detail
     *
     * @param Article|Detail $model
     * @return array
     */
    private function getImportedImages($model)
    {
        if (!$model instanceof Article && !$model instanceof Detail) {
            throw new \RuntimeException('Model must be instance of Article or Detail!');
        }

        $localArticleImagesFromConnect = [];

        /** @var \Shopware\Models\Article\Image $image */
        foreach ($model->getImages() as $image) {
            if ($model instanceof Detail && $model->getId() == $image->getArticleDetail()->getId()) {
                $image = $image->getParent();
            }
            $media = $image->getMedia();

            try {
                if (!$media || !$media->getAttribute()) {
                    continue;
                }
                $attribute = $media->getAttribute();
            } catch (\Doctrine\ORM\EntityNotFoundException $e) {
                //is thrown if media was deleted -> simply continue
                continue;
            }

            // If the image was not imported from connect, skip it
            $connectHash = $attribute->getConnectHash();
            if (!$connectHash) {
                continue;
            }
            $localArticleImagesFromConnect[$connectHash] = ['image' => $image, 'media' => $media];
        }

        return $localArticleImagesFromConnect;
    }

    /**
     * @param $imageUrl
     * @param Supplier $supplier
     */
    public function importImageForSupplier($imageUrl, Supplier $supplier)
    {
        try {
            $album = $this->manager->find('Shopware\Models\Media\Album', -12);
            $tempDir = Shopware()->DocPath('media_temp');

            $tempFile = tempnam($tempDir, 'image');
            copy($imageUrl, $tempFile);
            $file = new File($tempFile);

            $media = new Media();
            $media->setAlbum($album);
            $media->setDescription('');
            $media->setCreated(new \DateTime());
            $media->setUserId(0);
            $media->setFile($file);

            $this->manager->persist($media);

            $this->thumbnailManager->createMediaThumbnail(
                $media,
                $this->getThumbnailSize($album),
                true
            );

            $supplier->setImage($media->getPath());
            $this->manager->persist($supplier);

            $this->manager->flush();
        } catch (\Exception $e) {
            $this->logger->write(
                true,
                'import image for supplier',
                $e->getMessage() . 'imageUrl:' . $imageUrl
            );
        }
    }

    /**
     * Returns thumbnails size by album
     * @param $album \Shopware\Models\Media\Album
     * @return array
     */
    protected function getThumbnailSize($album)
    {
        if (!$album->getId()) {
            return;
        }

        $thumbnailSizes = $album->getSettings()->getThumbnailSize();
        $sizesArray = [];
        $requiredSizeExists = false;
        foreach ($thumbnailSizes as $size) {
            if (strlen($size) == 0) {
                continue;
            }

            $sizes = explode('x', $size);
            if ($sizes[0] == 140 && $sizes[1] == 140) {
                $requiredSizeExists = true;
            }
            $sizesArray[] = $size;
        }

        if ($requiredSizeExists === false) {
            $sizesArray[] = '140x140';
        }

        return $sizesArray;
    }

    /**
     * @param $imageUrl string
     * @param $articleId int
     */
    public function importMainImage($imageUrl, $articleId)
    {
        $oldMainImageId = $this->manager->getConnection()->fetchColumn('SELECT id FROM s_articles_img WHERE articleID = ? AND main = 1 AND parent_id IS NULL',
            [$articleId]);

        $newMainImageId = $this->manager->getConnection()->fetchColumn('
            SELECT s_articles_img.id 
            FROM s_articles_img 
            INNER JOIN s_media ON s_articles_img.media_id = s_media.id
            INNER JOIN s_media_attributes ON s_media.id = s_media_attributes.mediaID
            WHERE s_articles_img.articleID = ? AND s_articles_img.parent_id IS NULL AND s_media_attributes.connect_hash = ?',
            [$articleId, $imageUrl]);

        if ($newMainImageId && $newMainImageId !== $oldMainImageId) {
            $this->manager->getConnection()->executeQuery('
            UPDATE s_articles_img SET main = ? WHERE id = ?',
            [0, $oldMainImageId]);
            $this->manager->getConnection()->executeQuery('
            UPDATE s_articles_img SET main = ? WHERE id = ?',
             [1, $newMainImageId]);
        }
    }

    /**
     * @param $imageUrl string
     * @param $articleId int
     * @return bool
     */
    public function hasMainImageChanged($imageUrl, $articleId)
    {
        $result = $this->manager->getConnection()->fetchColumn('
            SELECT s_articles_img.id 
            FROM s_articles_img 
            INNER JOIN s_media ON s_articles_img.media_id = s_media.id
            INNER JOIN s_media_attributes ON s_media.id = s_media_attributes.mediaID
            WHERE s_articles_img.articleID = ? AND s_articles_img.main = 1 AND s_articles_img.parent_id IS NULL AND s_media_attributes.connect_hash = ?',
            [$articleId, $imageUrl]);

        return !(bool) $result;
    }
}
