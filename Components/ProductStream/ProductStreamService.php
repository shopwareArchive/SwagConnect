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
    const STATUS_DELETE = 'delete';
    const STATUS_ERROR = 'error';

    /**
     * @var ProductStreamRepository
     */
    private $productStreamRepository;

    /**
     * @var ProductStreamAttributeRepository
     */
    private $streamAttrRepository;

    /**
     * ProductStreamService constructor.
     * @param ProductStreamRepository $productStreamRepository
     * @param ProductStreamAttributeRepository $streamAttrRepository
     */
    public function __construct(
        ProductStreamRepository $productStreamRepository,
        ProductStreamAttributeRepository $streamAttrRepository
    ) {
        $this->productStreamRepository = $productStreamRepository;
        $this->streamAttrRepository = $streamAttrRepository;
    }

    /**
     * @param $streamId
     * @return ProductStreamsAssignments
     * @throws \Exception
     */
    public function prepareStreamsAssignments($streamId)
    {
        $stream = $this->findStream($streamId);

        $articleIds = $this->getArticlesIds($stream);

        $assignment = $this->collectRelatedStreamsAssignments($articleIds);

        //merge prev with current streams
        foreach ($articleIds as $articleId) {
            $assignment[$articleId][$stream->getId()] = $stream->getName();
        }

        return new ProductStreamsAssignments(
            array('assignments' => $assignment)
        );
    }

    /**
     * @param $streamId
     * @return ProductStreamsAssignments
     * @throws \Exception
     */
    public function getStreamAssignments($streamId)
    {
        //checks stream existence
        $stream = $this->findStream($streamId);

        $articleIds = $this->getArticlesIds($stream);

        $assignment = $this->collectRelatedStreamsAssignments($articleIds);

        return new ProductStreamsAssignments(
            array('assignments' => $assignment)
        );
    }

    /**
     * @param $streamId
     * @return mixed
     */
    public function findStream($streamId)
    {
        return $this->productStreamRepository->findById($streamId);
    }

    /**
     * @param $articleIds
     * @return array
     */
    private function collectRelatedStreamsAssignments($articleIds)
    {
        $assignment = array();

        $collection = $this->productStreamRepository->fetchAllPreviousExportedStreams($articleIds);

        //prepare previous related streams
        foreach ($collection as $item) {
            $assignment[$item['articleId']][$item['streamId']] = $item['name'];
        }

        return $assignment;
    }

    /**
     * @param ProductStreamsAssignments $assignments
     * @param $streamId
     * @param $articleId
     * @return bool
     */
    public function allowToRemove(ProductStreamsAssignments $assignments, $streamId, $articleId)
    {
        $streamAssignments = $assignments->getStreamsByArticleId($articleId);

        if (!$streamAssignments || !isset($streamAssignments[$streamId])) {
            return false;
        }

        if (count($streamAssignments) > 1) {
            return false;
        }

        return true;
    }

    /**
     * @param ProductStream $stream
     * @return array
     * @throws \Exception
     */
    public function getArticlesIds(ProductStream $stream)
    {
        if ($this->isStatic($stream)) {
            $sourceIds = $this->extractSourceIdsFromStaticStream($stream);
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
        return $this->productStreamRepository->fetchArticlesIds($productStream->getId());
    }

    /**
     * @param null $start
     * @param null $limit
     * @return array
     */
    public function getList($start = null, $limit = null)
    {
        $streamBuilder = $this->productStreamRepository->getStreamsBuilder($start, $limit);

        $streams = $streamBuilder->execute()->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($streams as $index => $stream) {
            if ($stream['type'] == self::STATIC_STREAM) {
                $productCount = $this->productStreamRepository->countProductsInStream($stream['id']);
                $streams[$index]['productCount'] = $productCount['productCount'];
            }
        }

        return array('success' => true, 'data' => $streams, 'total' => $streamBuilder->execute()->rowCount());
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
    public function log($streamId, $message)
    {
        /** @var ProductStreamAttribute $streamAttr */
        $streamAttr = $this->streamAttrRepository->findOneBy(array('streamId' => $streamId));
        $streamAttr->setExportMessage($message);

        $this->streamAttrRepository->save($streamAttr);
    }
}