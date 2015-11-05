<?php


namespace Shopware\Connect\Components\OrderQuery;


class RemoteOrderQuery
{
    private $repository;

    public function getConnectOrder($localOrderId)
    {
        $builder = $this->getRepository()->getBackendOrdersQueryBuilder();
        $builder->andWhere('attribute.connectOrderId = :connectOrderId')
            ->setParameter('connectOrderId', $localOrderId);

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