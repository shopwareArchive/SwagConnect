<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\CustomModels\Connect;

use Shopware\Components\Model\ModelRepository;

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

        if (isset($order[0]) && isset($order['property']) && isset($order['direction'])) {
            $builder->addOrderBy($order['property'], $order['direction']);
        }

        $builder->setFirstResult($start);
        $builder->setMaxResults($limit);

        return $builder;
    }
}
