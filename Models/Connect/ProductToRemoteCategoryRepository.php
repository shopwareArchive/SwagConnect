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
 * Class ProductToRemoteCategoryRepository
 * @package Shopware\CustomModels\Connect
 */
class ProductToRemoteCategoryRepository extends ModelRepository
{
    public function findArticlesByRemoteCategory($remoteCategoryKey, $limit = 10, $offset = 0)
    {
        $builder = $this->createQueryBuilder('ptrc');
        $builder->addSelect('a.id as Article_id');
        $builder->addSelect('md.number as Detail_number');
        $builder->addSelect('a.name as Article_name');
        $builder->addSelect('a.active as Article_active');
        $builder->addSelect('p.price as Price_basePrice');
        $builder->addSelect('t.tax as Tax_name');
        $builder->addSelect('s.name as Supplier_name');
        $builder->leftJoin('ptrc.connectCategory', 'rc');
        $builder->leftJoin('ptrc.article', 'a');
        $builder->leftJoin('a.mainDetail', 'md');
        $builder->leftJoin('md.prices', 'p');
        $builder->leftJoin('a.supplier', 's');
        $builder->leftJoin('a.tax', 't');
        $builder->leftJoin('a.attribute', 'att');
        $builder->where('rc.categoryKey = :categoryKey');
        $builder->setParameter('categoryKey', $remoteCategoryKey);
        $builder->andWhere('att.connectMappedCategory IS NULL');
        $builder->distinct(true);
        $builder->setFirstResult($offset);
        $builder->setMaxResults($limit);

        $query = $builder->getQuery();

        return $query;
    }

    /**
     * Collect article ids by given
     * remote category key
     * @param string $remoteCategoryKey
     * @return array
     */
    public function findArticleIdsByRemoteCategory($remoteCategoryKey)
    {
        $builder = $this->createQueryBuilder('ptrc');
        $builder->select('a.id');
        $builder->leftJoin('ptrc.connectCategory', 'rc');
        $builder->leftJoin('ptrc.article', 'a');
        $builder->leftJoin('a.attribute', 'att');
        $builder->where('rc.categoryKey = :categoryKey');
        $builder->andWhere('att.connectMappedCategory IS NULL');
        $builder->setParameter('categoryKey', $remoteCategoryKey);

        $query = $builder->getQuery();
        $query->setHydrationMode($query::HYDRATE_OBJECT);
        $result = $query->getArrayResult();

        return array_map(function($resultItem) {
            return $resultItem['id'];
        }, $result);
    }
}