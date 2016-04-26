<?php

namespace ShopwarePlugins\Connect\Components;

use Shopware\Components\Model\ModelManager;
use \Shopware\Models\Article\Image;
use \Shopware\Models\Media\Media;
use \Shopware\Models\Attribute\Media as MediaAttribute;
use Symfony\Component\HttpFoundation\File\File;
use Shopware\Models\Article\Supplier;
use Shopware\Components\Thumbnail\Manager as ThumbnailManager;


class ImageImport
{

    /** @var \Shopware\Components\Model\ModelManager */
    protected $manager;

    /** @var  Helper */
    protected $helper;

    /**
     * @var ThumbnailManager
     */
    protected $thumbnailManager;

    /** @var  \ShopwarePlugins\Connect\Components\Logger */
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
     * @param null $limit
     */
    public function import($limit=null)
    {
        $articleRepository = $this->manager->getRepository('Shopware\Models\Article\Article');

        $flags = $this->helper->getUpdateFlags();
        $flagsByName = array_flip($flags);

        $ids = $this->getProductsNeedingImageImport($limit);

        foreach ($ids as $id) {
            /** @var \Shopware\Models\Article\Article $model */
            $model = $articleRepository->find($id);
            $connectAttribute = $this->helper->getConnectAttributeByModel($model);

            $lastUpdate = json_decode($connectAttribute->getLastUpdate(), true);

            $this->importImagesForArticle($lastUpdate['image'], $model);

            $connectAttribute->flipLastUpdateFlag($flagsByName['imageInitialImport']);

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
     * @param $model \Shopware\Models\Article\Article
     */
    public function importImagesForArticle($images, $model)
    {
        // Build up an array of images imported from connect
        $positions = array(0);
        $localImagesFromConnect = array();
        $this->helper->profFlag("Collect already downloaded images from Connect");
        /** @var $image Image */
        /** @var $media \Shopware\Models\Media\Media */
        foreach ($model->getImages() as $image) {
            $image->setMain(2);
            // Build a list of used position fields
            $positions[] = $image->getPosition();

            $media = $image->getMedia();
            if (!$media || !$media->getAttribute()) {
                continue;
            }
            $attribute = $media->getAttribute();

            // If the image was not imported from connect, skip it
            $connectHash = $attribute->getConnectHash();
            if (!$connectHash) {
                continue;
            }

            if (isset($images[0]) && $connectHash == $images[0]) {
                $image->setMain(1);
            }

            $localImagesFromConnect[$connectHash] = array('image' => $image, 'media' => $media);
        }
        $this->helper->profFlag("Max position");
        $maxPosition = max($positions); // Get the highest position field

        $remoteImagesFromConnect = array_flip($images);

        $this->helper->profFlag("Images to delete");
        // Build up arrays of images to delete and images to create
        $imagesToDelete = array_diff_key($localImagesFromConnect, $remoteImagesFromConnect);
        $this->helper->profFlag("Images to create");
        $imagesToCreate = array_diff_key($remoteImagesFromConnect, $localImagesFromConnect);

        $this->helper->profFlag("Delete images");
        // Delete old connect images and media objects
        foreach ($imagesToDelete as $hash => $data) {
            $this->manager->remove($data['image']);
            $this->manager->remove($data['media']);
        }
        $this->helper->profFlag("Flush");
        $this->manager->flush();
        // Check if we still have a main image
        $hasMainImage = $this->hasArticleMainImage($model->getId());

        try {
            /** @var \Shopware\Models\Media\Album $album */
            $album = $this->manager->find('Shopware\Models\Media\Album', -1);
            $tempDir = Shopware()->DocPath('media_temp');
            foreach ($imagesToCreate as $imageUrl => $key) {
                $tempFile = tempnam($tempDir, 'image');
                $this->helper->profFlag("Copy image - " . $imageUrl);
                copy($imageUrl, $tempFile);
                $this->helper->profFlag("Create file object");
                $file = new File($tempFile);

                $this->helper->profFlag("Create media object");
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

                $this->helper->profFlag("Persist media and attribute");
                $this->manager->persist($media);
                $this->manager->persist($mediaAttribute);

                $this->helper->profFlag("Create image object");
                // Create the associated image object
                $image = new Image();
                // If there is no main image and we are in the first iteration, set the current image as main image
                $image->setMain((!$hasMainImage && $key == 0) ? 1 : 2);
                $image->setMedia($media);
                $image->setPosition($maxPosition + $key + 1);
                $image->setArticle($model);
                $image->setPath($media->getName());
                $image->setExtension($media->getExtension());

                $this->helper->profFlag("Persist image object");
                $this->manager->persist($image);

                $this->helper->profFlag("Create media thumbnail");
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

        $this->helper->profFlag("2nd flush");
        $this->manager->flush();
        $this->manager->clear();

        $this->helper->profPrint();
    }

    /**
     * @param $imageUrl
     * @param Supplier $supplier
     */
    public function importImageForSupplier($imageUrl, Supplier $supplier)
    {
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
        $sizesArray = array();
        $requiredSizeExists = false;
        foreach ($thumbnailSizes as $size) {
            $sizes = explode('x', $size);
            if ($sizes[0] == 140 && $sizes[1] == 140) {
                $requiredSizeExists = true;
            }
            $sizesArray[] = $sizes;
        }

        if ($requiredSizeExists === false) {
            $sizesArray[] = array(140, 140);
        }

        return $sizesArray;
    }

}