<?php
/**
 * Shopware 5
 * Copyright (c) shopware AG
 *
 * According to our dual licensing model, this program can be used either
 * under the terms of the GNU Affero General Public License, version 3,
 * or under a proprietary license.
 *
 * The texts of the GNU Affero General Public License with an additional
 * permission and of our proprietary license can be found at and
 * in the LICENSE file you have received along with this program.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * "Shopware" is a registered trademark of shopware AG.
 * The licensing of the program under the AGPLv3 does not imply a
 * trademark license. Therefore any rights, title and interest in
 * our trademarks remain entirely with us.
 */
namespace Shopware\CustomModels\Connect;

use \Shopware\Components\Model\ModelRepository;

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
     * List with all changed products which are comming from Connect
     *
     * @param int $start
     * @param int $limit
     * @param array $order
     * @return \Shopware\Components\Model\QueryBuilder
     */
    public function getChangedProducts($start, $limit, array $order = [])
    {
        $builder = $this->_em->createQueryBuilder();
        $builder->from('Shopware\CustomModels\Connect\Attribute', 'at');
        $builder->join('at.article', 'a');
        $builder->join('at.articleDetail', 'd');
        $builder->join('d.attribute', 'cad');
        $builder->leftJoin('d.prices', 'p', 'with', "p.from = 1 AND p.customerGroupKey = 'EK'");
        $builder->leftJoin('a.supplier', 's');
        $builder->leftJoin('a.tax', 't');

        $builder->select(array(
            'at.lastUpdate',
            'at.lastUpdateFlag as lastUpdateFlag',
            'a.id as articleId',
            'd.id',
            'd.number as number',
            'd.inStock as inStock',
            'cad.connectProductDescription as additionalDescription',
            'a.name as name',
            'a.description',
            'a.descriptionLong',
            's.name as supplier',
            'a.active as active',
            't.tax as tax',
            'p.price * (100 + t.tax) / 100 as price',
            'at.category'
        ));

        $builder->where('at.shopId IS NOT NULL')
            ->andWHere('at.lastUpdateFlag IS NOT NULL')
            ->andWHere('at.lastUpdateFlag > 0');

        $builder->addOrderBy($order);
        $query = $builder->getQuery();
        $query->setFirstResult($start);
        $query->setMaxResults($limit);

        return $builder;
    }
} 