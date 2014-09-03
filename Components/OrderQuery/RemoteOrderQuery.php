<?php


namespace Shopware\Bepado\Components\OrderQuery;


class RemoteOrderQuery
{
    private $repository;

    public function getBepadoOrder($localOrderId)
    {
        $builder = $this->getRepository()->getBackendOrdersQueryBuilder();
        $builder->andWhere('attribute.bepadoOrderId = :bepadoOrderId')
            ->setParameter('bepadoOrderId', $localOrderId);

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