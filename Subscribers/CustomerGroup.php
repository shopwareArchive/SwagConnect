<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Subscribers;

use ShopwarePlugins\Connect\Components\Logger;

/**
 * Hides the connect customer group for actions other then article
 *
 * Class CustomerGroup
 * @package ShopwarePlugins\Connect\Subscribers
 */
class CustomerGroup extends BaseSubscriber
{
    public function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Action_PreDispatch_Backend_Base' => 'filterCustomerGroup',
            'Shopware\Models\Customer\Repository::getCustomerGroupsQueryBuilder::after' => 'filterCustomerGroupFromQueryBuilder',
            'Shopware\Models\Customer\Repository::getCustomerGroupsWithoutIdsQueryBuilder::before' => 'addToWithoutIdsQueryBuilder'
        ];
    }

    /**
     * Remove 'connect' customer group from the base store - except 'showConnect' is set
     *
     * @param \Enlight_Event_EventArgs $args
     */
    public function filterCustomerGroup(\Enlight_Event_EventArgs $args)
    {
        /** @var \Enlight_Controller_Action $controller */
        $controller = $args->get('subject');
        $request = $controller->Request();

        if ($request->getActionName() != 'getCustomerGroups') {
            return;
        }

        try {
            if (!$this->getConnectCustomerGroupId()) {
                return;
            }
            if ($request->getParam('showConnect', false)) {
                return;
            }
            $filter = $request->getParam('filter', []);
            $filter[] = ['property' => 'id', 'value' => $this->getConnectCustomerGroupId(), 'expression' => '<>'];
            $request->setParam('filter', $filter);
        } catch (\Exception $e) {
            $logger = new Logger(Shopware()->Db());
            $logger->write(true, 'filterCustomerGroup', $e->getMessage());

            return;
        }
    }

    /**
     * This one will remove connect from the default customer group query
     *
     * @param \Enlight_Hook_HookArgs $args
     */
    public function filterCustomerGroupFromQueryBuilder(\Enlight_Hook_HookArgs $args)
    {
        if (!$this->getConnectCustomerGroupId()) {
            return;
        }

        // Allow the article module to list the connect customer group
        $pathInfo = Shopware()->Front()->Request()->getPathInfo();
        if (strpos($pathInfo, '/backend/Article') !== false) {
            return;
        }

        $builder = $args->getReturn();
        $builder->andWhere('groups.id != :groupId')->setParameter('groupId', $this->getConnectCustomerGroupId());

        $args->setReturn($builder);
    }

    /**
     * This one is used by the category module e.g.
     *
     * @param \Enlight_Hook_HookArgs $args
     */
    public function addToWithoutIdsQueryBuilder(\Enlight_Hook_HookArgs $args)
    {
        if (!$this->getConnectCustomerGroupId()) {
            return;
        }

        $userIds = $args->get('usedIds');

        if (!$userIds) {
            $userIds = [];
        }
        $userIds[] = $this->getConnectCustomerGroupId();

        $args->set('usedIds', $userIds);
    }

    /**
     * Will return the id of the connect customer group - or null if no such group can be found
     *
     * @return int|null
     */
    public function getConnectCustomerGroupId()
    {
        $repo = Shopware()->Models()->getRepository('Shopware\Models\Attribute\CustomerGroup');
        /** @var \Shopware\Models\Attribute\CustomerGroup $model */
        $model = $repo->findOneBy(['connectGroup' => true]);

        $customerGroup = null;
        if ($model && $model->getCustomerGroup()) {
            return $model->getCustomerGroup()->getId();
        }

        return null;
    }
}
