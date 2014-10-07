<?php


namespace Shopware\Bepado\Components\OrderQuery;


class RemoteOrderQuery
{
    private $repository;

    public function getBepadoOrder($localOrderId, $orderShop)
    {
        $builder = $this->getRepository()->getBackendOrdersQueryBuilder();
        $builder->andWhere('attribute.bepadoOrderId = :bepadoOrderId')
            ->andWhere('attribute.bepadoShopId = :bepadoShopId')
            ->setParameter('bepadoOrderId', $localOrderId)
            ->setParameter('bepadoShopId', $orderShop);

        /** @var \Shopware\Models\Order\Order $order */
        $order = $builder->getQuery()->getOneOrNullResult();

        return $order;
    }

    private function getRepository()
    {
        if (!$this->repository) {
            $this->repository = Shopware()->Models()->getRepository('Shopware\Models\Order\Order');
        }

        return $this->repository;
    }
} 