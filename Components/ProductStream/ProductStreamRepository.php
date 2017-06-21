<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Components\ProductStream;

use Doctrine\DBAL\Connection;
use Shopware\Components\Model\ModelManager;
use Shopware\Components\ProductStream\Repository;
use Shopware\CustomModels\Connect\ProductStreamAttribute;
use Shopware\Models\ProductStream\ProductStream;

class ProductStreamRepository extends Repository
{
    const CHUNK_LIMIT = 500;

    /**
     * @var \Shopware\Components\Model\ModelManager
     */
    private $manager;

    /**
     * ProductStreamRepository constructor.
     *
     * @param ModelManager $manager
     */
    public function __construct(ModelManager $manager)
    {
        parent::__construct($manager->getConnection());
        $this->manager = $manager;
    }

    /**
     * @param $streamId
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     *
     * @return ProductStream
     */
    public function findById($streamId)
    {
        $builder = $this->manager->createQueryBuilder();

        return $builder->select('ps')
            ->from('Shopware\Models\ProductStream\ProductStream', 'ps')
            ->where('ps.id = :streamId')
            ->setParameter('streamId', $streamId)
            ->getQuery()
            ->getSingleResult();
    }

    /**
     * @param array $streamIds
     *
     * @return ProductStream[]
     */
    public function findByIds(array $streamIds)
    {
        $builder = $this->manager->createQueryBuilder();

        return $builder->select('ps')
            ->from('Shopware\Models\ProductStream\ProductStream', 'ps')
            ->where('ps.id IN (:streamIds)')
            ->setParameter('streamIds', $streamIds)
            ->getQuery()
            ->getResult();
    }

    /**
     * Filter the stream ids, it will return only the exported ones
     *
     * @param array $streamIds
     *
     * @return array
     */
    public function filterExportedStreams(array $streamIds)
    {
        $build = $this->manager->getConnection()->createQueryBuilder();
        $build->select(['pcs.stream_id'])
            ->from('s_plugin_connect_streams', 'pcs')
            ->where('pcs.stream_id IN (:streamIds)')
            ->andWhere('pcs.export_status IN (:status)')
            ->setParameter('streamIds', $streamIds, Connection::PARAM_STR_ARRAY)
            ->setParameter('status', ProductStreamService::EXPORTED_STATUSES, Connection::PARAM_STR_ARRAY);

        $items = $build->execute()->fetchAll(\PDO::FETCH_ASSOC);

        return array_map(function ($item) {
            return $item['stream_id'];
        }, $items);
    }

    /**
     * @param ProductStream $stream
     *
     * @return array
     */
    public function fetchArticleIdsFromStaticStream(ProductStream $stream)
    {
        $build = $this->manager->getConnection()->createQueryBuilder();
        $build->select(['product.id'])
            ->from('s_articles', 'product')
            ->innerJoin('product', 's_product_streams_selection', 'streamProducts', 'streamProducts.article_id = product.id')
            ->where('streamProducts.stream_id = :streamId')
            ->setParameter('streamId', $stream->getId());

        $items = $build->execute()->fetchAll(\PDO::FETCH_ASSOC);

        return array_map(function ($item) {
            return $item['id'];
        }, $items);
    }

    /**
     * @param ProductStream $stream
     *
     * @return array
     */
    public function fetchArticleIdsFromDynamicStream(ProductStream $stream)
    {
        $build = $this->manager->getConnection()->createQueryBuilder();
        $build->select(['product.id'])
            ->from('s_articles', 'product')
            ->leftJoin('product', 's_plugin_connect_streams_relation', 'streamProducts', 'streamProducts.article_id = product.id')
            ->where('streamProducts.stream_id = :streamId')
            ->setParameter('streamId', $stream->getId());

        $items = $build->execute()->fetchAll(\PDO::FETCH_ASSOC);

        return array_map(function ($item) {
            return $item['id'];
        }, $items);
    }

    /**
     * @param array $articleIds
     *
     * @return array
     */
    public function fetchAllPreviousExportedStreams(array $articleIds)
    {
        $staticStreams = $this->fetchPreviousExportStaticStreams($articleIds);
        $dynamicStreams = $this->fetchPreviousExportDynamicStreams($articleIds);

        return array_merge($staticStreams, $dynamicStreams);
    }

    /**
     * @param array $articleIds
     *
     * @return array
     */
    public function fetchPreviousExportStaticStreams(array $articleIds)
    {
        $build = $this->manager->getConnection()->createQueryBuilder();
        $build->select(['es.stream_id as streamId', 'pss.article_id as articleId', 'ps.name', '0 as deleted'])
            ->from('s_plugin_connect_streams', 'es')
            ->leftJoin('es', 's_product_streams_selection', 'pss', 'pss.stream_id = es.stream_id')
            ->leftJoin('es', 's_product_streams', 'ps', 'ps.id = es.stream_id')
            ->where('pss.article_id IN (:articleIds)')
            ->andWhere('es.export_status IN (:status)')
            ->setParameter('status', ProductStreamService::EXPORTED_STATUSES, Connection::PARAM_STR_ARRAY)
            ->setParameter('articleIds', $articleIds, Connection::PARAM_INT_ARRAY);

        return $build->execute()->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @param array $articleIds
     *
     * @return array
     */
    public function fetchPreviousExportDynamicStreams(array $articleIds)
    {
        $build = $this->manager->getConnection()->createQueryBuilder();
        $build->select(['es.stream_id as streamId', 'pcsr.article_id as articleId', 'ps.name', 'pcsr.deleted'])
            ->from('s_plugin_connect_streams', 'es')
            ->leftJoin('es', 's_plugin_connect_streams_relation', 'pcsr', 'pcsr.stream_id = es.stream_id')
            ->leftJoin('es', 's_product_streams', 'ps', 'ps.id = es.stream_id')
            ->where('pcsr.article_id IN (:articleIds)')
            ->andWhere('es.export_status IN (:status)')
            ->setParameter('status', ProductStreamService::EXPORTED_STATUSES, Connection::PARAM_STR_ARRAY)
            ->setParameter('articleIds', $articleIds, Connection::PARAM_INT_ARRAY);

        return $build->execute()->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @param $type
     *
     * @return ProductStream[]
     */
    public function fetchExportedStreams($type)
    {
        $builder = $this->manager->createQueryBuilder();

        $status = ProductStreamService::EXPORTED_STATUSES;
        $status[] = ProductStreamService::STATUS_PENDING;

        return $builder->select('ps')
            ->from('Shopware\CustomModels\Connect\ProductStreamAttribute', 'psa')
            ->leftJoin('Shopware\Models\ProductStream\ProductStream', 'ps', \Doctrine\ORM\Query\Expr\Join::WITH, 'ps.id = psa.streamId')
            ->where('ps.type = :type')
            ->andWhere('psa.exportStatus IN (:status)')
            ->setParameter('type', $type)
            ->setParameter('status', $status)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return \Doctrine\DBAL\Query\QueryBuilder
     */
    public function getStreamsBuilder()
    {
        $builder = $this->manager->getConnection()->createQueryBuilder();

        $columns = [
            'ps.id', 'ps.name', 'ps.type', 'ps.conditions',
            'pcs.export_status as exportStatus', 'pcs.export_message as exportMessage',
        ];

        $builder->select($columns)
            ->from('s_product_streams', 'ps')
            ->leftJoin('ps', 's_plugin_connect_streams', 'pcs', 'ps.id = pcs.stream_id')
            //!!! Do NOT remove the order by type
            //because there is a bug with ext js when we have grouping
            ->orderBy('ps.type', 'ASC');

        return $builder;
    }

    /**
     * @param null $start
     * @param null $limit
     *
     * @return array
     */
    public function fetchStreamList($start = null, $limit = null)
    {
        $streamBuilder = $this->getStreamsBuilder();
        $total = $streamBuilder->execute()->rowCount();

        if ($start) {
            $streamBuilder->setFirstResult($start);
        }

        if ($limit) {
            $streamBuilder->setMaxResults($limit);
        }

        $streams = $streamBuilder->execute()->fetchAll(\PDO::FETCH_ASSOC);

        return [
            'streams' => $streams,
            'total' => $total,
        ];
    }

    /**
     * @param $streamId
     * @param array $articleIds
     *
     * @throws \Doctrine\DBAL\DBALException
     *
     * @return bool
     */
    public function createStreamRelation($streamId, array $articleIds)
    {
        $queryValues = [];
        $insertData = [];

        $conn = $this->manager->getConnection();
        $sql = 'INSERT INTO `s_plugin_connect_streams_relation` (`stream_id`, `article_id`, `deleted`) VALUES ';

        foreach ($articleIds as $index => $articleId) {
            $queryValues[] = '(:streamId' . $index . ', :articleId' . $index . ', :deleted' . $index . ')';
            $insertData['streamId' . $index] = $streamId;
            $insertData['articleId' . $index] = $articleId;
            $insertData['deleted' . $index] = ProductStreamAttribute::STREAM_RELATION_ACTIVE;
        }

        //batch insert
        $sql .= implode(', ', $queryValues);
        $sql .= ' ON DUPLICATE KEY UPDATE deleted = VALUES(deleted)';
        $stmt = $conn->prepare($sql);

        return $stmt->execute($insertData);
    }

    /**
     * @param $streamId
     *
     * @return \Doctrine\DBAL\Driver\Statement|int
     */
    public function markProductsToBeRemovedFromStream($streamId)
    {
        $builder = $this->manager->getConnection()->createQueryBuilder();

        return $builder->update('s_plugin_connect_streams_relation', 'pcsr')
            ->set('pcsr.deleted', ':deleted')
            ->where('pcsr.stream_id = :streamId')
            ->setParameter('streamId', $streamId)
            ->setParameter('deleted', ProductStreamAttribute::STREAM_RELATION_DELETED)
            ->execute();
    }

    /**
     * @return \Doctrine\DBAL\Driver\Statement|int
     */
    public function removeMarkedStreamRelations()
    {
        $builder = $this->manager->getConnection()->createQueryBuilder();

        return $builder->delete('s_plugin_connect_streams_relation')
            ->where('deleted = :deleted')
            ->setParameter('deleted', ProductStreamAttribute::STREAM_RELATION_DELETED)
            ->execute();
    }

    /**
     * @param ProductStream $stream
     *
     * @return bool
     */
    public function activateConnectProductsByStream(ProductStream $stream)
    {
        $connection = $this->manager->getConnection();
        $connection->beginTransaction();

        try {
            $builder = $connection->createQueryBuilder();
            $articleIds = $builder->select('article_id')
                ->from('s_product_streams_selection')
                ->where('stream_id = :streamId')
                ->setParameter('streamId', $stream->getId())
                ->execute()->fetchAll();

            $articleIds = array_map(function ($item) {
                return $item['article_id'];
            }, $articleIds);

            if (!$articleIds) {
                return false;
            }

            $chunkArticleIds = array_chunk($articleIds, self::CHUNK_LIMIT);

            foreach ($chunkArticleIds as $chunk) {
                $this->activateConnectProducts($chunk);
            }

            $connection->commit();
        } catch (\Exception $e) {
            $connection->rollback();

            return false;
        }

        return true;
    }

    /**
     * @param array $articleIds
     */
    private function activateConnectProducts(array $articleIds)
    {
        $articleBuilder = $this->manager->getConnection()->createQueryBuilder();
        $articleBuilder->update('s_articles', 'a')
            ->set('a.active', ':active')
            ->where('a.id IN (:articleIds)')
            ->setParameter('active', 1)
            ->setParameter('articleIds', $articleIds, \Doctrine\DBAL\Connection::PARAM_INT_ARRAY)
            ->execute();

        $variantBuilder = $this->manager->getConnection()->createQueryBuilder();
        $variantBuilder->update('s_articles_details', 'ad')
            ->set('ad.active', ':active')
            ->where('ad.articleID IN (:articleIds)')
            ->setParameter('active', 1)
            ->setParameter('articleIds', $articleIds, \Doctrine\DBAL\Connection::PARAM_INT_ARRAY)
            ->execute();
    }

    /**
     * @return array
     */
    public function fetchConnectStreamIds()
    {
        $builder = $this->manager->getConnection()->createQueryBuilder();

        $results = $builder->select('sa.streamID')
            ->from('s_product_streams_attributes', 'sa')
            ->where('sa.connect_is_remote = 1')
            ->execute()
            ->fetchAll();

        return array_map(function ($item) {
            return $item['streamID'];
        }, $results);
    }

    /**
     * @param $streamId
     *
     * @return bool|string
     */
    public function countProductsInStaticStream($streamId)
    {
        $builder = $this->manager->getConnection()->createQueryBuilder();

        return $builder->select('COUNT(ad.id) as productCount')
            ->from('s_articles_details', 'ad')
            ->leftJoin('ad', 's_product_streams_selection', 'pss', 'ad.articleID = pss.article_id')
            ->where('pss.stream_id = :streamId')
            ->setParameter('streamId', $streamId)
            ->execute()->fetchColumn();
    }
}
