<?php

namespace Shopware\Bepado\Components\ShippingCosts;


use Shopware\CustomModels\Bepado\ShippingGroup;

class ShippingGroups
{
    private $em;

    /**
     * @param string $name
     * @return ShippingGroup
     */
    public function create($name)
    {
        $model = new ShippingGroup();
        $model->setGroupName($name);

        $this->getEntityManager()->persist($model);
        $this->getEntityManager()->flush();

        return true;
    }

    private function getEntityManager()
    {
        if (!$this->em) {
            $this->em = Shopware()->Models();
        }

        return $this->em;
    }
} 