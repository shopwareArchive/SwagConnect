<?php
/**
 * Shopware 4.0
 * Copyright © 2013 shopware AG
 *
 * According to our dual licensing model, this program can be used either
 * under the terms of the GNU Affero General Public License, version 3,
 * or under a proprietary license.
 *
 * The texts of the GNU Affero General Public License with an additional
 * permission and of our proprietary license can be found at and
 * in the LICENSE file you have received along with this program.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * "Shopware" is a registered trademark of shopware AG.
 * The licensing of the program under the AGPLv3 does not imply a
 * trademark license. Therefore any rights, title and interest in
 * our trademarks remain entirely with us.
 */

/**
 * @category  Shopware
 * @package   Shopware\Plugins\SwagBepado
 * @copyright Copyright (c) 2013, shopware AG (http://www.shopware.de)
 */
class Shopware_Controllers_Backend_ShippingGroups extends Shopware_Controllers_Backend_ExtJs
{
    /**
     * @var \Shopware\Bepado\Components\ShippingCosts\ShippingGroups
     */
    private $shippingGroupsComponent;

    /**
     * Creates single shipping rule
     */
    public function createShippingRuleAction()
    {
        if ($this->Request()->getMethod() === 'POST') {
            $params = $this->Request()->getParams();

            try {
                $shippingGroupsComponent = $this->getShippingGroupsComponent();
                $rule = $shippingGroupsComponent->createRule($params);

                $shippingGroupsComponent->updateAffectedArticles($rule->getGroup()->getGroupName());
                $this->View()->assign(
                    array(
                        'success' => true,
                    )
                );
            } catch (\Exception $e) {
                $this->View()->assign(
                    array(
                        'success' => false
                    )
                );
            }
        }
    }

    /**
     * Returns shipping groups
     */
    public function getShippingGroupsAction()
    {
        $builder = $this->getModelManager()->createQueryBuilder();

        $builder->select('sg');
        $builder->from('Shopware\CustomModels\Bepado\ShippingGroup', 'sg');

        $groups = $builder->getQuery()->getArrayResult();

        $this->View()->assign(
            array(
                'success' => true,
                'data' => $groups,
            )
        );
    }

    /**
     * Creates shipping group
     */
    public function createShippingGroupAction()
    {
        if ($this->Request()->getMethod() === 'POST') {
            $groupName = $this->Request()->getParam('groupName');

            try {
                $shippingGroupsComponent = $this->getShippingGroupsComponent();
                $shippingGroupsComponent->createGroup($groupName);

                $this->View()->assign(
                    array(
                        'success' => true,
                    )
                );
            } catch (\Exception $e) {
                $this->View()->assign(
                    array(
                        'success' => false
                    )
                );
            }
        }
    }

    /**
     * Saves shipping rules
     */
    public function saveShippingRulesAction()
    {
        if ($this->Request()->getMethod() === 'POST') {
            $params = $this->Request()->getParam('data');

            if (isset($params['id'])) {
                $params = array($params);
            }

            try {
                $shippingGroupsComponent = $this->getShippingGroupsComponent();
                foreach ($params as $record) {
                    $shippingGroupsComponent->updateRule($record);
                }

                if (isset($params[0]['groupName'])) {
                    $shippingGroupsComponent->updateAffectedArticles($params[0]['groupName']);
                }

                $this->View()->assign(
                    array(
                        'success' => true,
                    )
                );
            } catch (\Exception $e) {
                $this->View()->assign(
                    array(
                        'success' => false,
                        'message' => $e->getMessage()
                    )
                );
            }
        }
    }

    /**
     * Returns shipping rules
     */
    public function getShippingRulesAction()
    {
        $start = $this->Request()->getParam('start', 0);
        $limit = $this->Request()->getParam('limit', 25) + $start;

        $builder = $this->getModelManager()->createQueryBuilder();

        $builder->select('sg, sr');
        $builder->from('Shopware\CustomModels\Bepado\ShippingRule', 'sr');
        $builder->join('sr.group', 'sg');

        $rules = $builder->getQuery()
            ->setFirstResult($start)
            ->setMaxResults($limit)
            ->getArrayResult();

        $total = $builder->select('COUNT(sr)')
            ->getQuery()
            ->getSingleScalarResult();

        $data = array_map(function($rule) {
            $rule['groupName'] = $rule['group']['groupName'];
            unset($rule['group']);

            return $rule;
        }, $rules);

        $this->View()->assign(
            array(
                'success' => true,
                'data' => $data,
                'total' => $total
            )
        );
    }

    /**
     * Deletes shipping rule by provided id
     */
    public function deleteShippingRuleAction()
    {
        if ($this->Request()->getMethod() === 'POST') {
            $params = $this->Request()->getParam('data');

            if (isset($params['id'])) {
                $params = array($params);
            }

            try {
                $shippingGroupsComponent = $this->getShippingGroupsComponent();
                foreach ($params as $record) {
                    $shippingGroupsComponent->deleteRule($record['id']);
                }

                if (isset($params[0]['groupName'])) {
                    $shippingGroupsComponent->updateAffectedArticles($params[0]['groupName']);
                }

                $this->View()->assign(
                    array(
                        'success' => true,
                    )
                );
            } catch (\Exception $e) {
                $this->View()->assign(
                    array(
                        'success' => false,
                        'message' => $e->getMessage()
                    )
                );
            }
        }
    }

    public function deleteShippingGroupAction()
    {
        try {
            $groupName = $this->Request()->getParam('groupName');
            $this->getShippingGroupsComponent()->deleteGroup($groupName);
            $this->getShippingGroupsComponent()->updateAffectedArticles($groupName);

            $this->View()->assign(array('success' => true));
        } catch (\Exception $e) {
            $this->View()->assign(
                array(
                    'success' => false,
                    'message' => $e->getMessage()
                )
            );
        }
    }

    /**
     * Returns instance of ShippingGroups component
     *
     * @return \Shopware\Bepado\Components\ShippingCosts\ShippingGroups
     */
    private function getShippingGroupsComponent()
    {
        if (!$this->shippingGroupsComponent) {
            $this->shippingGroupsComponent = new \Shopware\Bepado\Components\ShippingCosts\ShippingGroups();
        }

        return $this->shippingGroupsComponent;
    }

    public function getModelManager()
    {
        return Shopware()->Models();
    }
}