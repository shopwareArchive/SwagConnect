<?php

namespace Shopware\Bepado\Components;

use Shopware\Components\Model\ModelManager;
use \Shopware\Models\Article\Image;
use \Shopware\Models\Media\Media;
use \Shopware\Models\Attribute\Media as MediaAttribute;
use Symfony\Component\HttpFoundation\File\File;


class ImageImport
{

    /** @var \Shopware\Components\Model\ModelManager */
    protected $manager;

    /** @var  Helper */
    protected $helper;


    public function __construct(ModelManager $manager, Helper $helper)
    {
        $this->manager = $manager;
        $this->helper = $helper;

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
    public function import($limit=null)
    {
        $articleRepository = $this->manager->getRepository('Shopware\Models\Article\Article');

        $flags = $this->helper->getUpdateFlags();
        $flagsByName = array_flip($flags);

        $ids = $this->getProductsNeedingImageImport($limit);

        foreach ($ids as $id) {
            /** @var \Shopware\Models\Article\Article $model */
            $model = $articleRepository->find($id);
            $bepadoAttribute = $this->helper->getBepadoAttributeByModel($model);

            $lastUpdate = json_decode($bepadoAttribute->getLastUpdate(), true);

            $this->importImagesForArticle($lastUpdate['image'], $model);

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
     * @param $model \Shopware\Models\Article\Article
     */
    public function importImagesForArticle($images, $model)
    {
        // Build up an array of images imported from bepado
        $positions = array(0);
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

        // Check if we still have a main image
        $hasMainImage = $this->hasArticleMainImage($model->getId());

        try {
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
                $mediaAttribute->setBepadoHash($imageUrl);
                $mediaAttribute->setMedia($media);

                $this->manager->persist($media);
                $this->manager->persist($mediaAttribute);

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

                $manager = Shopware()->Container()->get('thumbnail_manager');
                $manager->createMediaThumbnail(
                    $media,
                    $this->getThumbnailSize($album),
                    true
                );
            }
        } catch (\Exception $e) {
        }

        $this->manager->flush();
        $this->manager->clear();
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
        foreach ($thumbnailSizes as $size) {
            $sizes = explode('x', $size);
            $sizesArray[] = $sizes;
        }

        return $sizesArray;
    }

}