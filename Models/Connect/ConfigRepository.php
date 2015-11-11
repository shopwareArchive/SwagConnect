<?php

namespace   Shopware\CustomModels\Connect;

use         \Shopware\Components\Model\ModelRepository;

class ConfigRepository extends ModelRepository
{

    public function getConfigsQuery($name=null, $shopId=null, $groupName=null)
    {
        $builder = $this->getConfigsQueryBuilder($name, $shopId, $groupName);

        return $builder->getQuery();
    }

    public function getConfigsQueryBuilder($name, $shopId, $groupName)
    {
        $builder = $this->createQueryBuilder('c');

        if (!is_null($shopId)) {
            $builder->where('c.shopId = :shopId');
            $builder->setParameter(':shopId', $shopId);
        } else {
            $builder->where('c.shopId IS NULL');
        }

        if (!is_null($name)) {
            $builder->andWhere('c.name = :name');
            $builder->setParameter(':name', $name);
        }

        if (!is_null($groupName)) {
            $builder->andWhere('c.groupName = :groupName');
            $builder->setParameter(':groupName', $groupName);
        }

        return $builder;
    }

    public function remove(Config $config)
    {
        $this->getEntityManager()->remove($config);
        $this->getEntityManager()->flush();
    }

}
