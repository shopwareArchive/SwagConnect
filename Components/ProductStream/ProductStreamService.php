<?php

namespace ShopwarePlugins\Connect\Components\ProductStream;

use Shopware\Bundle\SearchBundle\ProductSearch;
use Shopware\Bundle\SearchBundle\ProductSearchResult;
use Shopware\Bundle\StoreFrontBundle\Struct\ProductContext;
use Shopware\CustomModels\Connect\ProductStreamAttribute;
use Shopware\Models\ProductStream\ProductStream;
use Shopware\CustomModels\Connect\ProductStreamAttributeRepository;
use Shopware\Bundle\SearchBundle\Criteria;
use Shopware\Bundle\SearchBundle\ConditionInterface;
use Shopware\Bundle\StoreFrontBundle\Service\Core\ContextService;
use Shopware\Bundle\SearchBundle\Condition\CategoryCondition;
use Shopware\Bundle\SearchBundle\Condition\CustomerGroupCondition;
use ShopwarePlugins\Connect\Components\Config;

class ProductStreamService
{
    const DYNAMIC_STREAM = 1;
    const STATIC_STREAM = 2;
    const STATUS_EXPORT = 'export';
    const STATUS_DELETE = 'delete';
    const STATUS_ERROR = 'error';
    const STATUS_SYNCED = 'synced';

    /**
     * @var ProductStreamRepository
     */
    private $productStreamRepository;

    /**
     * @var ProductStreamAttributeRepository
     */
    private $streamAttrRepository;

    /** @var Config */
    private $config;

    /** @var ProductSearch */
    private $productSearchService;

    /** @var ContextService */
    private $contextService;

    /**
     * ProductStreamService constructor.
     * @param ProductStreamRepository $productStreamRepository
     * @param ProductStreamAttributeRepository $streamAttrRepository
     * @param Config $config
     * @param ProductSearch $productSearchService
     * @param ContextService $contextService
     */
    public function __construct(
        ProductStreamRepository $productStreamRepository,
        ProductStreamAttributeRepository $streamAttrRepository,
        Config $config,
        ProductSearch $productSearchService,
        ContextService $contextService
    ) {
        $this->productStreamRepository = $productStreamRepository;
        $this->streamAttrRepository = $streamAttrRepository;
        $this->config = $config;
        $this->productSearchService = $productSearchService;
        $this->contextService = $contextService;
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
     * @param array $streamIds
     * @return \Shopware\Models\ProductStream\ProductStream[]
     */
    public function findStreams(array $streamIds)
    {
        return $this->productStreamRepository->findByIds($streamIds);
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

        $stmt = $streamBuilder->execute();
        $streams = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($streams as $index => $stream) {
            if ($stream['type'] == self::STATIC_STREAM) {
                $streams[$index]['productCount'] = $this->countProductsInStaticStream($stream['id']);
            } else {
                $productStream = $this->productStreamRepository->findById($stream['id']);
                $result = $this->getProductFromConditionStream($productStream);
                $streams[$index]['productCount'] = $result->getTotalCount();
            }
        }

        return [
            'data' => $streams,
            'count' => $stmt->rowCount()
        ];
    }

    /**
     * @param ProductStream $stream
     * @return ProductStreamAttribute
     */
    public function createStreamAttribute(ProductStream $stream)
    {
        $streamAttribute = $this->streamAttrRepository->findOneBy(['streamId' => $stream->getId()]);

        if (!$streamAttribute) {
            $streamAttribute = $this->streamAttrRepository->create();
            $streamAttribute->setStreamId($stream->getId());
        }

        return $streamAttribute;
    }

    /**
     * @param $streamId
     * @return string
     */
    public function countProductsInStaticStream($streamId)
    {
        return $this->productStreamRepository->countProductsInStaticStream($streamId);
    }

    /**
     * @param ProductStream $stream
     * @return ProductSearchResult
     */
    public function getProductFromConditionStream(ProductStream $stream)
    {
        $criteria = new Criteria();

        $conditions = json_decode($stream->getConditions(), true);
        $conditions = $this->productStreamRepository->unserialize($conditions);

        foreach ($conditions as $condition) {
            $criteria->addCondition($condition);
        }

        $sorting = json_decode($stream->getSorting(), true);
        $sorting = $this->productStreamRepository->unserialize($sorting);

        foreach ($sorting as $sort) {
            $criteria->addSorting($sort);
        }

        /** @var ProductContext $context */
        $context = $this->contextService->createShopContext($this->config->getDefaultShopId());

        $criteria->addBaseCondition(
            new CustomerGroupCondition([$context->getCurrentCustomerGroup()->getId()])
        );

        $criteria->addBaseCondition(
            new CategoryCondition([$context->getShop()->getCategory()->getId()])
        );

        return $this->productSearchService->search($criteria, $context);
    }

    /**
     * @param $streamId
     * @return bool
     */
    public function isStreamExported($streamId)
    {
        return $this->streamAttrRepository->isStreamExported($streamId);
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