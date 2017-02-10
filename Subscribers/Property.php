<?php

namespace ShopwarePlugins\Connect\Subscribers;


/**
 * Class Property
 * @package ShopwarePlugins\Connect\Subscribers
 */
class Property extends BaseSubscriber
{

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
                    'backend/property/view/main/set_grid_connect.js'
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
            default:
                break;
        }
    }

    public function markConnectGroupsProperties($data)
    {
        $data[0]['connect'] = true;
        return $data;
    }

    public function markConnectSetsProperties($data)
    {
        $data[0]['connect'] = true;
        return $data;
    }
}