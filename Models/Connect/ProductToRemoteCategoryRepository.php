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
use \Doctrine\ORM\Query\Expr\Join;

/**
 * Class ProductToRemoteCategoryRepository
 * @package Shopware\CustomModels\Connect
 */
class ProductToRemoteCategoryRepository extends ModelRepository
{
    /**
     * @param string $remoteCategoryKey
     * @param int $shopId
     * @param int $limit
     * @param int $offset
     * @return \Doctrine\ORM\Query
     */
    public function findArticlesByRemoteCategory($remoteCategoryKey = null, $shopId, $stream = null, $limit = 10, $offset = 0, $hideMapped = true)
    {
        $builder = $this->getEntityManager()->createQueryBuilder();
        $builder->select(array(
            'pci',
            'a.id as Article_id',
            'a.name as Article_name',
            'a.active as Article_active',
            'md.number as Detail_number',
            's.name as Supplier_name',
            'pci.purchasePrice as Price_basePrice',
            't.tax as Tax_name',
        ))
            ->from('Shopware\CustomModels\Connect\Attribute', 'pci')
            ->leftJoin('Shopware\CustomModels\Connect\ProductToRemoteCategory', 'ptrc', Join::WITH, 'ptrc.articleId = pci.articleId')
            ->leftJoin('pci.article', 'a')
            ->leftJoin('a.mainDetail', 'md')
            ->leftJoin('a.supplier', 's')
            ->leftJoin('a.tax', 't')
            ->leftJoin('a.attribute', 'att')
            ->where('pci.shopId = :shopId')
            ->setParameter('shopId', $shopId)
            ->groupBy('pci.articleId')
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        if ($remoteCategoryKey != null) {
            $builder->leftJoin('ptrc.connectCategory', 'rc')
                ->andWhere('rc.categoryKey = :categoryKey')
                ->setParameter('categoryKey', $remoteCategoryKey);
        }

        if ($hideMapped) {
            $builder->andWhere('att.connectMappedCategory IS NULL');
        }

        if ($stream != null) {
            $builder->andWhere('pci.stream = :stream')
                ->setParameter('stream', $stream);
        }

        return $builder->getQuery();
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

    /**
     * Collects article ids by given
     * remote category name
     * @param string $remoteCategoryName
     * @return array
     */
    public function findArticleIdsByRemoteCategoryName($remoteCategoryName)
    {
        $builder = $this->createQueryBuilder('ptrc');
        $builder->select('a.id');
        $builder->leftJoin('ptrc.connectCategory', 'rc');
        $builder->leftJoin('ptrc.article', 'a');
        $builder->leftJoin('a.attribute', 'att');
        $builder->where('rc.label = :label');
        $builder->setParameter('label', $remoteCategoryName);

        $query = $builder->getQuery();
        $query->setHydrationMode($query::HYDRATE_OBJECT);
        $result = $query->getArrayResult();

        return array_map(function($resultItem) {
            return $resultItem['id'];
        }, $result);
    }
}
