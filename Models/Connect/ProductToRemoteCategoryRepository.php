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
            ->leftJoin(
                'Shopware\Models\Article\Price',
                'p',
                Join::WITH,
                "p.articleDetailsId = md.id AND p.customerGroupKey = 'EK' AND p.from = 1"
            )
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
            $builder->andWhere('att.connectMappedCategory <> 1');
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
     * @param int $shopId
     * @param string $stream
     * @param int $offset
     * @param int $limit
     * @return array
     */
    public function findArticleIdsByRemoteCategoryAndStream($remoteCategoryKey, $shopId, $stream, $offset, $limit)
    {
        return $this->getEntityManager()->getConnection()->executeQuery(
            'SELECT DISTINCT pci.article_id
            FROM s_plugin_connect_items AS pci
            INNER JOIN s_plugin_connect_product_to_categories AS ptrc ON ptrc.articleID = pci.article_id
            INNER JOIN s_plugin_connect_categories AS pcc ON pcc.id = ptrc.connect_category_id
            WHERE pcc.category_key = ? AND pcc.shop_id = ? AND pci.stream = ?
            ORDER BY pci.article_id DESC
            LIMIT ?
            OFFSET ?',
            [$remoteCategoryKey, $shopId, $stream, $limit, $offset],
            [\PDO::PARAM_STR, \PDO::PARAM_INT, \PDO::PARAM_STR, \PDO::PARAM_INT, \PDO::PARAM_INT]
        )->fetchAll(\PDO::FETCH_COLUMN);
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
     * @return int
     */
    public function deleteByConnectCategoryId($categoryId, $articleId)
    {
        $builder = $this->createQueryBuilder('ptrc');
        $builder->delete('Shopware\CustomModels\Connect\ProductToRemoteCategory', 'ptrc');
        $builder->where('ptrc.connectCategoryId = :ccid');
        $builder->andWhere('ptrc.articleId = :articleId');
        $builder->setParameter(':ccid', $categoryId, \PDO::PARAM_INT);
        $builder->setParameter(':articleId', $articleId, \PDO::PARAM_INT);
        $builder->getQuery()->execute();
    }

    /**
     * @param string $remoteCategoryKey
     * @param int $shopId
     * @param string $stream
     * @return int
     */
    public function getArticleCountByRemoteCategoryAndStream($remoteCategoryKey, $shopId, $stream)
    {
        return $this->getEntityManager()->getConnection()->fetchColumn(
            'SELECT COUNT(DISTINCT pci.article_id)
            FROM s_plugin_connect_items AS pci
            INNER JOIN s_plugin_connect_product_to_categories AS ptrc ON ptrc.articleID = pci.article_id
            INNER JOIN s_plugin_connect_categories AS pcc ON pcc.id = ptrc.connect_category_id
            WHERE pcc.category_key = ? AND pcc.shop_id = ? AND pci.stream = ?',
            [$remoteCategoryKey, $shopId, $stream]
        );
    }
}
