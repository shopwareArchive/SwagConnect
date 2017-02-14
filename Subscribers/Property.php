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
                // The "Groups" in the frontend are actually "s_filter_options" in the database and the entity is called Option ?!
                $subject->View()->data = $this->markRecordsAsConnect(
                    $subject->View()->data,
                    'Shopware\Models\Property\Option'
                );
                break;
            case 'getSets':
                // The "Sets" in the frontend are actually "s_filter" in the database and the entity is called Group ?!
                $subject->View()->data = $this->markRecordsAsConnect(
                    $subject->View()->data,
                    'Shopware\Models\Property\Group'
                );
                break;
            case 'getOptions':
                // The "Options" in the frontend are actually "s_filter_values" in the database and the entity is called Value ?!
                $subject->View()->data = $this->markRecordsAsConnect(
                    $subject->View()->data,
                    'Shopware\Models\Property\Value'
                );
                break;
            case 'getSetAssigns':
                $subject->View()->data = $this->markAssignsAsConnect(
                    $subject->View()->data
                );
                break;
            default:
                break;
        }
    }

    public function markRecordsAsConnect($data, $modelName)
    {
        $result = [];

        foreach ($data as $row) {
            $recordId = $row['id'];
            $model = $this->modelManager->getRepository($modelName)->find($recordId);

            $attribute = null;
            if ($model) {
                $attribute = $model->getAttribute();
            }

            if ($attribute && $attribute->getConnectIsRemote()) {
                $row['connect'] = true;
            }

            $result[] = $row;
        }

        return $result;
    }

    public function markAssignsAsConnect($data)
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