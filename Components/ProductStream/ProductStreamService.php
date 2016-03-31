<?php

namespace ShopwarePlugins\Connect\Components\ProductStream;

use Shopware\CustomModels\Connect\ProductStreamAttribute;
use Shopware\Models\ProductStream\ProductStream;
use Shopware\CustomModels\Connect\ProductStreamAttributeRepository;

class ProductStreamService
{
    const DYNAMIC_STREAM = 1;
    const STATIC_STREAM = 2;
    const STATUS_SUCCESS = 'export';
    const STATUS_ERROR = 'error';

    /**
     * @var ProductStreamRepository
     */
    private $productStreamQuery;

    /**
     * @var ProductStreamAttributeRepository
     */
    private $streamAttrRepository;

    /**
     * ProductStreamService constructor.
     * @param ProductStreamRepository $productStreamQuery
     * @param ProductStreamAttributeRepository $streamAttrRepository
     */
    public function __construct(
        ProductStreamRepository $productStreamQuery,
        ProductStreamAttributeRepository $streamAttrRepository
    ) {
        $this->productStreamQuery = $productStreamQuery;
        $this->streamAttrRepository = $streamAttrRepository;
    }

    /**
     * @param $streamId
     * @return ProductStreamsAssignments
     * @throws \Exception
     */
    public function prepareStreamsAssignments($streamId)
    {
        $assignment = array();

        $stream = $this->productStreamQuery->findById($streamId);

        $articleIds = $this->getArticlesIds($streamId);

        $collection = $this->productStreamQuery->fetchAllPreviousExportedStreams($articleIds);

        foreach ($collection as $item) {
            $assignment[$item['articleId']][$item['streamId']] = $item['name'];
        }

        foreach ($articleIds as $articleId) {
            $assignment[$articleId][$stream->getId()] = $stream->getName();
        }

        return new ProductStreamsAssignments(
            array('assignments' => $assignment)
        );
    }

    /**
     * @param $streamId
     * @return array
     * @throws \Exception
     */
    public function getArticlesIds($streamId)
    {
        $sourceIds = array();

        $stream = $this->productStreamQuery->findById($streamId);

        if ($this->isStatic($stream)) {
            $sourceIds = array_merge($sourceIds, $this->extractSourceIdsFromStaticStream($stream));
        } else {
            //todo: extract product from dynamic stream
            throw new \Exception('Not allow to export articles ids from dynamic stream');
        }

        return $sourceIds;
    }

    /**
     * @param ProductStream $productStream
     * @return bool
     */
    public function isStatic(ProductStream $productStream)
    {
        if ($productStream->getType() == self::STATIC_STREAM) {
            return true;
        }

        return false;
    }

    /**
     * @param ProductStream $productStream
     * @return array
     */
    public function extractSourceIdsFromStaticStream(ProductStream $productStream)
    {
        return $this->productStreamQuery->fetchArticlesIds($productStream->getId());
    }

    /**
     * @param $streamId
     * @param $status
     */
    public function changeStatus($streamId, $status)
    {
        $streamAttr = $this->streamAttrRepository->findOneBy(array('streamId' => (int) $streamId));

        if (!$streamAttr) {
            $streamAttr = $this->streamAttrRepository->create();
            $streamAttr->setStreamId($streamId);
        }

        $streamAttr->setExportStatus($status);
        $this->streamAttrRepository->save($streamAttr);
    }

    /**
     * @param $streamId
     * @param $message
     */
    public function logError($streamId, $message)
    {
        /** @var ProductStreamAttribute $streamAttr */
        $streamAttr = $this->streamAttrRepository->findOneBy(array('streamId' => $streamId));
        $streamAttr->setExportMessage($message);

        $this->streamAttrRepository->save($streamAttr);
    }
}