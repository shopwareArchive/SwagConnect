<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\CustomModels\Connect;

use Shopware\Components\Model\ModelRepository;
use Doctrine\ORM\Query\Expr\Join;

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
    public function findArticlesByRemoteCategory($remoteCategoryKey = null, $shopId, $stream = null, $limit = 10, $offset = 0, $hideMapped = true, $searchQuery = '')
    {
        $builder = $this->getEntityManager()->createQueryBuilder();
        $builder->select([
            'pci',
            'a.id as Article_id',
            'a.name as Article_name',
            'a.active as Article_active',
            'md.number as Detail_number',
            's.name as Supplier_name',
            'pci.purchasePrice as Price_basePrice',
            '(p.price * (1 + (t.tax / 100)) as Price_price',
            't.tax as Tax_name',
        ])
            ->from('Shopware\CustomModels\Connect\Attribute', 'pci')
            ->leftJoin('Shopware\CustomModels\Connect\ProductToRemoteCategory', 'ptrc', Join::WITH, 'ptrc.articleId = pci.articleId')
            ->leftJoin('pci.article', 'a')
            ->leftJoin('a.mainDetail', 'md')
            ->leftJoin('Shopware\Models\Article\Price',
                'p',
                Join::WITH,
                "p.articleDetailsId = md.id AND p.customerGroupKey = 'EK' AND p.from = 1")
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

        if (trim($searchQuery) !== '') {
            $builder->andWhere(
                $builder->expr()->orX(
                    $builder->expr()->orX('a.name LIKE :searchQuery'),
                    $builder->expr()->orX('s.name LIKE :searchQuery'),
                    $builder->expr()->orX('md.number LIKE :searchQuery')
                )
            )->setParameter('searchQuery', '%' . $searchQuery . '%');
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
        $builder->setParameter('categoryKey', $remoteCategoryKey);
        //distinct necessary because of variant articles
        //each variant has an own entry in a.attribute so same articleId iis returned multiple times
        $builder->distinct();

        $query = $builder->getQuery();
        $query->setHydrationMode($query::HYDRATE_OBJECT);
        $result = $query->getArrayResult();

        return array_map(function ($resultItem) {
            return $resultItem['id'];
        }, $result);
    }

    /**
     * @param int $articleId
     * return ProductToRemoteCategory[]
     */
    public function getArticleRemoteCategories($articleId)
    {
        $builder = $this->createQueryBuilder('ptrc');
        $builder->select('ptrc');
        $builder->where('ptrc.articleId = :articleId');
        $builder->setParameter('articleId', $articleId);

        $query = $builder->getQuery();

        return $query->getResult();
    }

    /**
     * @param int $articleId
     * @return int[]
     */
    public function getRemoteCategoryIds($articleId)
    {
        $builder = $this->createQueryBuilder('ptrc');
        $builder->select('ptrc.connectCategoryId');
        $builder->where('ptrc.articleId = :articleId');
        $builder->setParameter('articleId', $articleId, \PDO::PARAM_INT);

        $query = $builder->getQuery();
        $result = $query->getResult($query::HYDRATE_SCALAR);

        return array_map(
            function ($row) {
                return $row['connectCategoryId'];
            },
            $result
        );
    }

    /**
     * @param int $categoryId
     * @param int $articleId
     */
    public function deleteByConnectCategoryId($categoryId, $articleId)
    {
        $builder = $this->createQueryBuilder('ptrc');
        $builder->delete('Shopware\CustomModels\Connect\ProductToRemoteCategory', 'ptrc');
        $builder->where('ptrc.connectCategoryId = :ccid');
        $builder->andWhere('ptrc.articleId = :articleId');
        $builder->setParameter(':ccid', (int) $categoryId);
        $builder->setParameter(':articleId', (int) $articleId);
        $builder->getQuery()->execute();
    }
}
