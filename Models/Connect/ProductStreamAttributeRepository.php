<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\CustomModels\Connect;

use Shopware\Components\Model\ModelRepository;
use Shopware\Models\ProductStream\ProductStream;
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
                )
            )
            ->setParameter('exportedStatus', ProductStreamService::STATUS_EXPORT)
            ->setParameter('syncedStatus', ProductStreamService::STATUS_SYNCED);

        return (bool) $qb->execute()->fetchColumn();
    }

    public function resetExportedStatus()
    {
        $connection = $this->getEntityManager()->getConnection();
        $builder = $connection->createQueryBuilder();

        $builder->update('s_plugin_connect_streams', 'pcs')
            ->set('pcs.export_status', '(:newStatus)')
            ->where('pcs.export_status IN (:status)')
            ->setParameter('newStatus', null)
            ->setParameter(
                'status',
                [
                    ProductStreamService::STATUS_EXPORT,
                    ProductStreamService::STATUS_SYNCED,
                    ProductStreamService::STATUS_DELETE
                ],
                \Doctrine\DBAL\Connection::PARAM_STR_ARRAY
            );

        $builder->execute();
    }

    /**
     * @param $name
     * @return ProductStream
     */
    public function findConnectByName($name)
    {
        $builder = $this->_em->createQueryBuilder();

        return $builder->select('ps')
            ->from(ProductStream::class, 'ps')
            ->leftJoin('ps.attribute', 'psa')
            ->where('ps.name = :name')
            ->andWhere('psa.connectIsRemote = :connectIsRemote')
            ->setParameter('name', $name)
            ->setParameter('connectIsRemote', true)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
