<?php

namespace ShopwarePlugins\Connect\Components\ProductStream;

use Shopware\Components\Model\ModelManager;
use Shopware\Components\ProductStream\Repository;

class ProductStreamRepository extends Repository
{
    private $manager;

    public function __construct(ModelManager $manager)
    {
        parent::__construct($manager->getConnection());
        $this->manager = $manager;
    }

    public function findByIds(array $streamIds)
    {
        $builder = $this->manager->createQueryBuilder();

        return $builder->select('ps')
            ->from('Shopware\Models\ProductStream\ProductStream', 'ps')
            ->where("ps.id IN (:streamIds)")
            ->setParameter('streamIds', $streamIds)
            ->getQuery()
            ->getResult();
    }

    public function fetchArticlesIds($streamId)
    {
        $build = $this->manager->getConnection()->createQueryBuilder();
        $build->select(['product.id', 'variant.ordernumber as number', 'product.name'])
            ->from('s_articles', 'product')
            ->innerJoin('product', 's_product_streams_selection', 'streamProducts', 'streamProducts.article_id = product.id')
            ->innerJoin('product', 's_articles_details', 'variant', 'variant.id = product.main_detail_id')
            ->where('streamProducts.stream_id = :streamId')
            ->setParameter(':streamId', $streamId);

        $items = $build->execute()->fetchAll(\PDO::FETCH_ASSOC);

       return array_map(function($item){
            return $item['id'];
        }, $items);
    }

}