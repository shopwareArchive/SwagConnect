<?php

namespace Shopware\Bepado\Components\ShippingCosts;


use Shopware\CustomModels\Bepado\ShippingGroup;
use Shopware\CustomModels\Bepado\ShippingRule;

class ShippingGroups
{
    private $em;

    /**
     * @param string $name
     * @return ShippingGroup
     */
    public function createGroup($name)
    {
        $model = new ShippingGroup();
        $model->setGroupName($name);

        $this->getEntityManager()->persist($model);
        $this->getEntityManager()->flush();

        return true;
    }

    public function createRule(array $params)
    {
        if (!$params['groupId']) {
            throw new \Exception("Invalid shipping group id.");
        }

        $group = $this->getEntityManager()->getRepository('Shopware\CustomModels\Bepado\ShippingGroup')->find($params['groupId']);
        if (!$group) {
            throw new \Exception("Shipping group not found.");
        }

        $model = new ShippingRule();
        $model->setGroup($group);
        $model->setCountry($params['country']);
        $model->setDeliveryDays($params['deliveryDays']);
        $model->setPrice($params['price']);
        $model->setZipPrefix($params['zipPrefix']);

        $this->getEntityManager()->persist($model);
        $this->getEntityManager()->flush();
    }

    private function getEntityManager()
    {
        if (!$this->em) {
            $this->em = Shopware()->Models();
        }

        return $this->em;
    }
} 