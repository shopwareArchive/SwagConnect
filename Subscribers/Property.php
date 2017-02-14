<?php

namespace ShopwarePlugins\Connect\Subscribers;

use Shopware\Components\Model\ModelManager;

/**
 * Class Property
 * @package ShopwarePlugins\Connect\Subscribers
 */
class Property extends BaseSubscriber
{

    /**
     * @var ModelManager
     */
    private $modelManager;

    public function __construct(
        ModelManager $modelManager
    ) {
        parent::__construct();
        $this->modelManager = $modelManager;
    }

    public function getSubscribedEvents()
    {
        return array(
            'Enlight_Controller_Action_PostDispatch_Backend_Property' => 'extendBackendProperty',
        );
    }

    /**
     * @param \Enlight_Event_EventArgs $args
     */
    public function extendBackendProperty(\Enlight_Event_EventArgs $args)
    {
        /** @var $subject \Enlight_Controller_Action */
        $subject = $args->getSubject();
        $request = $subject->Request();

        switch ($request->getActionName()) {
            case 'load':
                $this->registerMyTemplateDir();
                $this->registerMySnippets();

                $subject->View()->extendsTemplate(
                    'backend/property/view/main/group_grid_connect.js'
                );

                $subject->View()->extendsTemplate(
                    'backend/property/model/group_connect.js'
                );

                $subject->View()->extendsTemplate(
                    'backend/property/view/main/set_grid_connect.js'
                );

                $subject->View()->extendsTemplate(
                    'backend/property/model/set_connect.js'
                );

                $subject->View()->extendsTemplate(
                    'backend/property/view/main/option_grid_connect.js'
                );

                $subject->View()->extendsTemplate(
                    'backend/property/model/option_connect.js'
                );

                $subject->View()->extendsTemplate(
                    'backend/property/view/main/set_assign_grid_connect.js'
                );

                $subject->View()->extendsTemplate(
                    'backend/property/model/set_assign_connect.js'
                );

                break;
            case 'getGroups':
                $subject->View()->data = $this->markConnectGroupsProperties(
                    $subject->View()->data
                );
                break;
            case 'getSets':
                $subject->View()->data = $this->markConnectSetsProperties(
                    $subject->View()->data
                );
                break;
            case 'getOptions':
                $subject->View()->data = $this->markConnectOptionProperties(
                    $subject->View()->data
                );
                break;
            case 'getSetAssigns':
                $subject->View()->data = $this->markSetAssignProperties(
                    $subject->View()->data
                );
                break;
            default:
                break;
        }
    }

    public function markConnectGroupsProperties($data)
    {
        // The "Groups" in the frontend are actually "s_filter_options" in the database and the entity is called Option ?!
        $options = [];

        foreach ($data as $option) {
            $optionId = $option['id'];
            /** @var \Shopware\Models\Property\Option $groupModel */
            $optionModel = $this->modelManager->getRepository('Shopware\Models\Property\Option')->find($optionId);

            $attribute = null;
            if ($optionModel) {
                $attribute = $optionModel->getAttribute();
            }

            if ($attribute && $attribute->getConnectIsRemote()) {
                $option['connect'] = true;
            }

            $options[] = $option;
        }

        return $options;
    }

    public function markConnectSetsProperties($data)
    {
        // The "Sets" in the frontend are actually "s_filter" in the database and the entity is called Group ?!
        $groups = [];

        foreach ($data as $group) {
            $groupId = $group['id'];
            /** @var \Shopware\Models\Property\Group $groupModel */
            $groupModel = $this->modelManager->getRepository('Shopware\Models\Property\Group')->find($groupId);

            $attribute = null;
            if ($groupModel) {
                $attribute = $groupModel->getAttribute();
            }

            if ($attribute && $attribute->getConnectIsRemote()) {
                $group['connect'] = true;
            }

            $groups[] = $group;
        }

        return $groups;
    }

    public function markConnectOptionProperties($data)
    {
        // The "Options" in the frontend are actually "s_filter_values" in the database and the entity is called Value ?!
        $values = [];

        foreach ($data as $value) {
            $valueId = $value['id'];
            /** @var \Shopware\Models\Property\Value $valueModel */
            $valueModel = $this->modelManager->getRepository('Shopware\Models\Property\Value')->find($valueId);

            $attribute = null;
            if ($valueModel) {
                $attribute = $valueModel->getAttribute();
            }

            if ($attribute && $attribute->getConnectIsRemote()) {
                $value['connect'] = true;
            }

            $values[] = $value;
        }

        return $values;
    }

    public function markSetAssignProperties($data)
    {
        // The set assigns display assigned Groups
        // The "Groups" in the frontend are actually "s_filter_options" in the database and the entity is called Option ?!
        $setAssigns = [];

        foreach ($data as $setAssign) {
            $optionId = $setAssign['groupId'];
            /** @var \Shopware\Models\Property\Option $groupModel */
            $optionModel = $this->modelManager->getRepository('Shopware\Models\Property\Option')->find($optionId);

            $attribute = null;
            if ($optionModel) {
                $attribute = $optionModel->getAttribute();
            }

            if ($attribute && $attribute->getConnectIsRemote()) {
                $setAssign['connect'] = true;
            }

            $setAssigns[] = $setAssign;
        }

        return $setAssigns;
    }
}