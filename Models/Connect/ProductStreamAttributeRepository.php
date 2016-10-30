<?php

namespace Shopware\CustomModels\Connect;

use \Shopware\Components\Model\ModelRepository;
use ShopwarePlugins\Connect\Components\ProductStream\ProductStreamService;

/**
 * Class ProductStreamAttributeRepository
 * @package Shopware\CustomModels\Connect
 */
class ProductStreamAttributeRepository extends ModelRepository
{
    /**
     * @param ProductStreamAttribute $productStreamAttribute
     */
    public function save(ProductStreamAttribute $productStreamAttribute)
    {
        $this->getEntityManager()->persist($productStreamAttribute);
        $this->getEntityManager()->flush($productStreamAttribute);
    }

    /**
     * @return ProductStreamAttribute
     */
    public function create()
    {
        return new ProductStreamAttribute();
    }

    /**
     * @param $streamId
     * @return bool
     */
    public function isStreamExported($streamId)
    {
        $connection = $this->getEntityManager()->getConnection();
        $qb = $connection->createQueryBuilder();
        $qb->select('pcs.id')
            ->from('s_plugin_connect_streams', 'pcs')
            ->where('pcs.stream_id = :streamId')
            ->setParameter('streamId', $streamId)
            ->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->eq('pcs.export_status', ':exportedStatus'),
                    $qb->expr()->eq('pcs.export_status', ':syncedStatus')
                ))
            ->setParameter('exportedStatus', ProductStreamService::STATUS_EXPORT)
            ->setParameter('syncedStatus', ProductStreamService::STATUS_SYNCED);

        return (bool)$qb->execute()->fetchColumn();
    }
}