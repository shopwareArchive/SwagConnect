<?php

namespace Shopware\Bepado\Components\ShippingCosts;

use Shopware\CustomModels\Bepado\ShippingGroup;
use Shopware\CustomModels\Bepado\ShippingRule;

class ShippingGroups
{
    /**
     * @var \Shopware\Components\Model\ModelManager
     */
    private $em;

    /**
     * @var \Shopware\Components\Model\ModelRepository
     */
    private $groupRepository;

    /**
     * @var \Shopware\Components\Model\ModelRepository
     */
    private $ruleRepository;

    public function __construct()
    {
        $this->em = Shopware()->Models();
        $this->groupRepository = Shopware()->Models()->getRepository('Shopware\CustomModels\Bepado\ShippingGroup');
        $this->ruleRepository = Shopware()->Models()->getRepository('Shopware\CustomModels\Bepado\ShippingRule');
    }

    /**
     * @param string $name
     * @return ShippingGroup
     */
    public function createGroup($name)
    {
        $model = new ShippingGroup();
        $model->setGroupName($name);

        $this->em->persist($model);
        $this->em->flush();

        return true;
    }

    /**
     * Helper function
     * Create shipping rule and save it to DB
     *
     * @param array $params
     * @return ShippingRule
     * @throws \Exception
     */
    public function createRule(array $params)
    {
        if (!$params['groupId']) {
            throw new \Exception("Invalid shipping group id.");
        }

        $group = $this->groupRepository->find((int)$params['groupId']);
        if (!$group) {
            throw new \Exception("Shipping group not found.");
        }

        $model = new ShippingRule();
        $model->setGroup($group);
        $model->setCountry($params['country']);
        $model->setDeliveryDays($params['deliveryDays']);
        $model->setPrice($params['price']);
        $model->setZipPrefix($params['zipPrefix']);

        $this->em->persist($model);
        $this->em->flush();

        return $model;
    }

    /**
     * Helper function
     * Update already created shipping rule
     *
     * @param array $rule
     * @return ShippingRule
     * @throws \Exception
     */
    public function updateRule(array $rule)
    {
        /** @var \Shopware\CustomModels\Bepado\ShippingRule $model */
        $model = $this->ruleRepository->find($rule['id']);

        if (!$model) {
            throw new \Exception('Shipping rule not found.');
        }

        $group = $this->groupRepository->find((int)$rule['groupId']);
        if (!$group) {
            throw new \Exception("Shipping group not found.");
        }

        $model->setCountry($rule['country']);
        $model->setDeliveryDays($rule['deliveryDays']);
        $model->setCountry($rule['country']);
        $model->setPrice($rule['price']);
        $model->setZipPrefix($rule['zipPrefix']);
        $model->setGroup($group);

        $this->em->persist($model);
        $this->em->flush();

        return $model;
    }

    /**
     * Helper function
     * Delete shipping rule from DB
     *
     * @param $id
     * @throws \Exception
     */
    public function deleteRule($id)
    {
        /** @var \Shopware\CustomModels\Bepado\ShippingRule $model */
        $model = $this->ruleRepository->find($id);
        if (!$model) {
            throw new \Exception('Shipping rule not found.');
        }

        $this->em->remove($model);
        $this->em->flush();
    }

    /**
     * Helper function
     * Generate shipping string
     *
     * @param $groupName
     * @return array|string
     */
    public function generateShippingString($groupName)
    {
        $shipping = '';
        /** @var \Shopware\CustomModels\Bepado\ShippingGroup $group */
        $group = $this->groupRepository->findOneBy(array('groupName' => $groupName));
        if (!$group) {
            return $shipping;
        }

        /** @var \Shopware\CustomModels\Bepado\ShippingRule $rule */
        foreach ($group->getRules() as $rule) {
            $shipping[] = sprintf('%s:%s:%s [%sD]:%.2f EUR',
                $rule->getCountry(),
                $rule->getZipPrefix(),
                $group->getGroupName(),
                $rule->getDeliveryDays(),
                $rule->getPrice()
            );
        }

        return implode(',', $shipping);
    }

    /**
     * Update all products containing group
     * @param $groupName
     */
    public function updateAffectedArticles($groupName)
    {
        $articles = $this->getAffectedArticles($groupName);
        $shipping = $this->generateShippingString($groupName);
        /** @var \Shopware\Models\Article\Article $article */
        foreach ($articles as $article) {
            $attribute = $article->getMainDetail()->getAttribute();
            $attribute->setBepadoArticleShipping($shipping);
            $this->em->persist($attribute);
        }

        $this->em->flush();
    }

    /**
     * Helper function
     * Extract group name from shipping string
     * @param $shipping
     * @return string
     */
    public function extractGroupName($shipping)
    {
        $shippingArray = explode(':', $shipping);
        if (!isset($shippingArray[2])) {
            return '';
        }
        $groupName = explode(' [', $shippingArray[2]);
        if (!isset($groupName[0])) {
            return '';
        }

        return $groupName[0];
    }

    /**
     * Find affected articles by shipping group name
     *
     * @param $groupName
     * @return array
     */
    public function getAffectedArticles($groupName)
    {
        $statement = Shopware()->Db()->prepare("SELECT articleID FROM s_articles_attributes WHERE bepado_article_shipping LIKE :groupName");
        $statement->bindValue(':groupName', "%$groupName%", \PDO::PARAM_STR);

        $statement->execute();
        $ids = $statement->fetchAll(\PDO::FETCH_COLUMN);

        if (empty($ids)) {
            return array();
        }

        return $this->em->getRepository('Shopware\Models\Article\Article')->findBy(array('id' => $ids));
    }
} 