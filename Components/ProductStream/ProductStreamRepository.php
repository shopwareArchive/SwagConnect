<?php

namespace ShopwarePlugins\Connect\Components\ProductStream;

use Shopware\Components\Model\ModelManager;
use Shopware\Components\ProductStream\Repository;

class ProductStreamRepository extends Repository
{
    /**
     * @var \Shopware\Components\Model\ModelManager
     */
    private $manager;

    /**
     * ProductStreamRepository constructor.
     * @param ModelManager $manager
     */
    public function __construct(ModelManager $manager)
    {
        parent::__construct($manager->getConnection());
        $this->manager = $manager;
    }

    /**
     * @param $streamId
     * @return mixed
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findById($streamId)
    {
        $builder = $this->manager->createQueryBuilder();

        return $builder->select('ps')
            ->from('Shopware\Models\ProductStream\ProductStream', 'ps')
            ->where("ps.id = :streamId")
            ->setParameter('streamId', $streamId)
            ->getQuery()
            ->getSingleResult();
    }

    /**
     * @param $streamId
     * @return array
     */
    public function fetchArticlesIds($streamId)
    {
        $build = $this->manager->getConnection()->createQueryBuilder();
        $build->select(array('product.id'))
            ->from('s_articles', 'product')
            ->innerJoin('product', 's_product_streams_selection', 'streamProducts', 'streamProducts.article_id = product.id')
            ->where('streamProducts.stream_id = :streamId')
            ->setParameter(':streamId', $streamId);

        $items = $build->execute()->fetchAll(\PDO::FETCH_ASSOC);

       return array_map(function($item){
            return $item['id'];
        }, $items);
    }

    /**
     * @param array $articleIds
     * @return array
     */
    public function fetchAllPreviousExportedStreams(array $articleIds)
    {
        $build = $this->manager->getConnection()->createQueryBuilder();
        $build->select(array('es.stream_id as streamId', 'pss.article_id as articleId', 'ps.name'))
            ->from('s_plugin_connect_streams', 'es')
            ->leftJoin('es', 's_product_streams_selection', 'pss', 'pss.stream_id = es.stream_id')
            ->leftJoin('es', 's_product_streams', 'ps', 'ps.id = es.stream_id')
            ->where('pss.article_id IN (:articleIds)')
            ->setParameter(':articleIds', $articleIds, \Doctrine\DBAL\Connection::PARAM_INT_ARRAY);

        return $build->execute()->fetchAll(\PDO::FETCH_ASSOC);
    }

}