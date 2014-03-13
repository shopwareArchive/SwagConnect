<?php

namespace Shopware\Bepado\Subscribers;

/**
 * Hides the bepado customer group for actions other then article
 *
 * Class CustomerGroup
 * @package Shopware\Bepado\Subscribers
 */
class CustomerGroup extends BaseSubscriber
{
    public function getSubscribedEvents()
    {
        return array(
            'Enlight_Controller_Action_PreDispatch_Backend_Base' => 'filterCustomerGroup',
            'Shopware\Models\Customer\Repository::getCustomerGroupsQueryBuilder::after' => 'filterCustomerGroupFromQueryBuilder',
            'Shopware\Models\Customer\Repository::getCustomerGroupsWithoutIdsQueryBuilder::before' => 'addToWithoutIdsQueryBuilder'
        );
    }

    /**
     * Remove 'bepado' customer group from the base store - except 'showBepado' is set
     *
     * @param \Enlight_Event_EventArgs $args
     */
    public function filterCustomerGroup(\Enlight_Event_EventArgs $args)
    {
        if (!$this->getBepadoCustomerGroupId()) {
            return;
        }
        
        /** @var \Enlight_Controller_Action $controller */
        $controller = $args->get('subject');
        $request = $controller->Request();

        if ($request->getParam('showBepado', false)) {
            return;
        }

        $filter = $request->getParam('filter', array());
        $filter[] = array("property" => "id", "value" => $this->getBepadoCustomerGroupId(), 'expression' => '<>');

        $request->setParam('filter', $filter);
    }

    /**
     * This one will remove bepado from the default customer group query
     *
     * @param \Enlight_Hook_HookArgs $args
     */
    public function filterCustomerGroupFromQueryBuilder(\Enlight_Hook_HookArgs $args)
    {
        if (!$this->getBepadoCustomerGroupId()) {
            return;
        }

        // Allow the article module to list the bepado customer group
        $pathInfo = Shopware()->Front()->Request()->getPathInfo();
        if (strpos($pathInfo, '/backend/Article') !== false) {
            return;
        }

        $builder = $args->getReturn();
        $builder->andWhere('groups.id != :groupId')->setParameter('groupId', $this->getBepadoCustomerGroupId());

        $args->setReturn($builder);
    }

    /**
     * This one is used by the category module e.g.
     *
     * @param \Enlight_Hook_HookArgs $args
     */
    public function addToWithoutIdsQueryBuilder(\Enlight_Hook_HookArgs $args)
    {
        if (!$this->getBepadoCustomerGroupId()) {
            return;
        }

        $userIds = $args->get('usedIds');

        if (!$userIds) {
            $userIds = array();
        }
        $userIds[] = $this->getBepadoCustomerGroupId();

        $args->set('usedIds', $userIds);
    }

    /**
     * Will return the id of the bepado customer group - or null if no such group can be found
     *
     * @return int|null
     */
    public function getBepadoCustomerGroupId()
    {
        $repo = Shopware()->Models()->getRepository('Shopware\Models\Attribute\CustomerGroup');
        /** @var \Shopware\Models\Attribute\CustomerGroup $model */
        $model = $repo->findOneBy(array('bepadoGroup' => true));

        $customerGroup = null;
        if ($model && $model->getCustomerGroup()) {
            return $model->getCustomerGroup()->getId();
        }

        return null;
    }

}