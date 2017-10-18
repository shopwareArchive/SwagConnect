<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\CustomModels\Connect;

use Shopware\Components\Model\ModelRepository;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Connection;

/**
 * Class AttributeRepository
 * @package Shopware\CustomModels\Connect
 */
class AttributeRepository extends ModelRepository
{
    public function findRemoteArticleAttributes()
    {
        return $this->getRemoteArticleAttributesQuery()->getResult();
    }

    public function getRemoteArticleAttributesQuery()
    {
        return $this->getRemoteArticleAttributesQueryBuilder()->getQuery();
    }

    public function getRemoteArticleAttributesQueryBuilder()
    {
        $builder = $this->createQueryBuilder('a');
        $builder->select('a');
        $builder->where('a.shopId IS NOT NULL')
                ->andWhere('a.category IS NOT NULL');

        return $builder;
    }

    /**
     * @param array $status
     * @return mixed
     */
    public function countStatus(array $status)
    {
        $builder = $this->createQueryBuilder('at');
        $builder
            ->select($builder->expr()->count('at.id'))
            ->where('at.exportStatus IN (:status)')
            ->setParameter(
                'status',
                $status,
                \Doctrine\DBAL\Connection::PARAM_STR_ARRAY
            );

        return $builder->getQuery()->getSingleScalarResult();
    }

    /**
     * Resets the exported items
     */
    public function resetExportedItemsStatus()
    {
        $builder = $this->_em->createQueryBuilder();
        $builder->update('Shopware\CustomModels\Connect\Attribute', 'a')
            ->set('a.exportStatus', '(:newStatus)')
            ->set('a.exported', 0)
            ->set('a.revision', '(:revision)')
            ->where('a.exportStatus IN (:status)')
            ->setParameter('newStatus', null)
            ->setParameter('revision', null)
            ->setParameter(
                'status',
                [Attribute::STATUS_INSERT, Attribute::STATUS_UPDATE, Attribute::STATUS_SYNCED],
                \Doctrine\DBAL\Connection::PARAM_STR_ARRAY
            );

        $builder->getQuery()->execute();
    }

    /**
     * List with all changed products which are coming from Connect
     *
     * @param int $start
     * @param int $limit
     * @param array $order
     * @return \Doctrine\DBAL\Query\QueryBuilder
     */
    public function getChangedProducts($start, $limit, array $order = [])
    {
        $builder = $this->_em->getConnection()->createQueryBuilder();
        $builder->from('s_plugin_connect_items', 'at');
        $builder->join('at', 's_articles', 'a', 'at.article_id = a.id');
        $builder->join('at', 's_articles_details', 'd', 'at.article_detail_id = d.id');
        $builder->join('at', 's_articles_attributes', 'cad', 'at.article_detail_id = cad.articleDetailsId');
        $builder->leftJoin('at', 's_articles_prices', 'p', "p.from = 1 AND p.pricegroup = 'EK'");
        $builder->leftJoin('a', 's_articles_supplier', 's', 'a.supplierID = s.id');
        $builder->leftJoin('a', 's_core_tax', 't', 'a.taxID = t.id');

        $builder->select([
            'at.last_update as lastUpdate',
            'at.last_update_flag as lastUpdateFlag',
            'a.id as articleId',
            'd.id',
            'd.ordernumber as number',
            'd.inStock as inStock',
            'cad.connect_product_description as additionalDescription',
            'a.name as name',
            'a.description',
            'a.description_long as descriptionLong',
            's.name as supplier',
            'a.active as active',
            't.tax as tax',
            'p.price * (100 + t.tax) / 100 as price',
            'at.category'
        ]);

        $builder->where('at.shop_id IS NOT NULL')
            ->andWHere('at.last_update_flag IS NOT NULL')
            ->andWHere('at.last_update_flag > 0');

        if (isset($order[0]) && isset($order[0]['property']) && isset($order[0]['direction'])) {
            $builder->addOrderBy($order[0]['property'], $order[0]['direction']);
        }

        $builder->setFirstResult($start);
        $builder->setMaxResults($limit);

        return $builder;
    }

    /**
     * @param int $offset
     * @param int $batchSize
     * @return array
     */
    public function findAllSourceIds($offset, $batchSize)
    {
        $customProductsTableExists = $this->hasCustomProductsTable();

        // main variants should be collected first, because they
        // should be exported first. Connect uses first variant product with an unknown groupId as main one.
        $builder = $this->_em->getConnection()->createQueryBuilder();
        $builder->select('spci.source_id')
            ->from('s_plugin_connect_items', 'spci')
            ->rightJoin('spci', 's_articles_details', 'sad', 'spci.article_detail_id = sad.id')
            ->where('sad.kind IN (1,2) AND spci.shop_id IS NULL')
            ->orderBy('sad.kind', 'ASC')
            ->addOrderBy('spci.source_id', 'ASC')
            ->setFirstResult($offset)
            ->setMaxResults($batchSize);

        if ($customProductsTableExists) {
            $builder->leftJoin('spci', 's_plugin_custom_products_template_product_relation', 'spcptpr', 'spci.article_id = spcptpr.article_id')
                ->andWhere('spcptpr.template_id IS NULL');
        }

        return $builder->execute()->fetchAll(\PDO::FETCH_COLUMN);
    }

    public function getLocalArticleCount()
    {
        $conn = $this->_em->getConnection();

        $sql = 'SELECT COUNT(article_id) FROM s_plugin_connect_items';

        $query = $conn->query($sql);

        return $query->fetchColumn();
    }

    /**
     * @param array $articleIds
     * @param int $kind
     * @return array
     */
    public function findSourceIds(array $articleIds, $kind)
    {
        $customProductsTableExists = $this->hasCustomProductsTable();

        // main variants should be collected first, because they
        // should be exported first. Connect uses first variant product with an unknown groupId as main one.
        $builder = $this->_em->getConnection()->createQueryBuilder();
        $builder->select('spci.source_id')
            ->from('s_plugin_connect_items', 'spci')
            ->rightJoin('spci', 's_articles_details', 'sad', 'spci.article_detail_id = sad.id')
            ->where('sad.articleID IN (:articleIds) AND sad.kind = :kind AND spci.shop_id IS NULL')
            ->setParameter(':articleIds', $articleIds, Connection::PARAM_INT_ARRAY)
            ->setParameter('kind', $kind, \PDO::PARAM_INT);

        if ($customProductsTableExists) {
            $builder->leftJoin('spci', 's_plugin_custom_products_template_product_relation', 'spcptpr', 'spci.article_id = spcptpr.article_id')
                ->andWhere('spcptpr.template_id IS NULL');
        }

        return $builder->execute()->fetchAll(\PDO::FETCH_COLUMN);
    }

    /**
     * @return bool
     */
    private function hasCustomProductsTable()
    {
        $customProductsTableExists = false;
        try {
            $builder = $this->_em->getConnection()->createQueryBuilder();
            $builder->select('id');
            $builder->from('s_plugin_custom_products_template');
            $builder->setMaxResults(1);
            $builder->execute()->fetch();

            $customProductsTableExists = true;
        } catch (DBALException $e) {
            // ignore it
            // custom products is not installed
        }

        return $customProductsTableExists;
    }
}
