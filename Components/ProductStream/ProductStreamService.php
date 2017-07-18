<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Components\ProductStream;

use Shopware\Bundle\SearchBundle\ProductSearch;
use Shopware\Bundle\SearchBundle\ProductSearchResult;
use Shopware\Bundle\StoreFrontBundle\Struct\ProductContext;
use Shopware\CustomModels\Connect\ProductStreamAttribute;
use Shopware\Models\ProductStream\ProductStream;
use Shopware\CustomModels\Connect\ProductStreamAttributeRepository;
use Shopware\Bundle\SearchBundle\Criteria;
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
    const STATUS_PENDING = 'pending';
    const PRODUCT_LIMIT = 100;

    //Available statuses for exported stream
    const EXPORTED_STATUSES = [
        self::STATUS_EXPORT,
        self::STATUS_SYNCED,
        self::STATUS_ERROR,
    ];

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
     * @param bool $appendCurrent
     * @return ProductStreamsAssignments
     */
    public function prepareStreamsAssignments($streamId, $appendCurrent = true)
    {
        $stream = $this->findStream($streamId);

        $articleIds = $this->getArticlesIds($stream);

        $assignment = $this->collectRelatedStreamsAssignments($articleIds);

        if ($appendCurrent) {
            //merge prev with current streams
            foreach ($articleIds as $articleId) {
                $assignment[$articleId][$stream->getId()] = $stream->getName();
            }
        }

        return new ProductStreamsAssignments(
            ['assignments' => $assignment]
        );
    }

    /**
     * @param $streamId
     * @throws \Exception
     * @return ProductStreamsAssignments
     */
    public function getStreamAssignments($streamId)
    {
        //checks stream existence
        $stream = $this->findStream($streamId);

        $articleIds = $this->getArticlesIds($stream);

        $assignment = $this->collectRelatedStreamsAssignments($articleIds);

        return new ProductStreamsAssignments(
            ['assignments' => $assignment]
        );
    }

    /**
     * @param ProductStream $stream
     * @return bool
     */
    public function isConnectStream(ProductStream $stream)
    {
        $attribute = $stream->getAttribute();

        if (!$attribute) {
            return false;
        }

        return (bool) $attribute->getConnectIsRemote();
    }

    /**
     * @param ProductStream $stream
     * @return bool
     */
    public function activateConnectProductsByStream(ProductStream $stream)
    {
        return $this->productStreamRepository->activateConnectProductsByStream($stream);
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
     * Filter the stream ids, it will return only the exported ones
     *
     * @param array $streamIds
     * @return array
     */
    public function filterExportedStreams(array $streamIds)
    {
        return $this->productStreamRepository->filterExportedStreams($streamIds);
    }

    /**
     * @param $articleIds
     * @return array
     */
    private function collectRelatedStreamsAssignments($articleIds)
    {
        $assignment = [];

        $collection = $this->productStreamRepository->fetchAllPreviousExportedStreams($articleIds);

        //prepare previous related streams
        foreach ($collection as $item) {
            //does not append the streams which were marked deleted
            if ($item['deleted'] == ProductStreamAttribute::STREAM_RELATION_DELETED) {
                if (!isset($assignment[$item['articleId']])) {
                    //adds empty array if there is no other stream for this product
                    $assignment[$item['articleId']] = [];
                }

                continue;
            }

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
     */
    public function getArticlesIds(ProductStream $stream)
    {
        if ($this->isStatic($stream)) {
            return $this->productStreamRepository->fetchArticleIdsFromStaticStream($stream);
        }

        return $this->productStreamRepository->fetchArticleIdsFromDynamicStream($stream);
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
     * @param null $start
     * @param null $limit
     * @return array
     */
    public function getList($start = null, $limit = null)
    {
        $streamList = $this->productStreamRepository->fetchStreamList($start, $limit);

        $isCronActive = $this->config->isCronActive();

        $streams = $streamList['streams'];
        foreach ($streams as $index => $stream) {
            if ($stream['type'] == self::STATIC_STREAM) {
                $streams[$index]['productCount'] = $this->countProductsInStaticStream($stream['id']);
            } elseif ($stream['type'] == self::DYNAMIC_STREAM) {
                $streams[$index]['enableRow'] = $isCronActive;
            }
        }

        return [
            'data' => $streams,
            'total' => $streamList['total']
        ];
    }

    /**
     * @param $type
     * @return array
     */
    public function getAllExportedStreams($type)
    {
        return $this->productStreamRepository->fetchExportedStreams($type);
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
     * @return \Doctrine\DBAL\Driver\Statement|int
     */
    public function markProductsToBeRemovedFromStream($streamId)
    {
        return $this->productStreamRepository->markProductsToBeRemovedFromStream($streamId);
    }

    /**
     * @param $streamId
     * @param array $articleIds
     * @return array
     */
    public function createStreamRelation($streamId, array $articleIds)
    {
        return $this->productStreamRepository->createStreamRelation($streamId, $articleIds);
    }

    /**
     * @return \Doctrine\DBAL\Driver\Statement|int
     */
    public function removeMarkedStreamRelations()
    {
        return $this->productStreamRepository->removeMarkedStreamRelations();
    }

    /**
     * @return array
     */
    public function getConnectStreamIds()
    {
        return $this->productStreamRepository->fetchConnectStreamIds();
    }

    /**
     * @param $streamId
     * @param $status
     */
    public function changeStatus($streamId, $status)
    {
        $streamAttr = $this->streamAttrRepository->findOneBy(['streamId' => (int) $streamId]);

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
        $streamAttr = $this->streamAttrRepository->findOneBy(['streamId' => $streamId]);
        $streamAttr->setExportMessage($message);

        $this->streamAttrRepository->save($streamAttr);
    }
}
