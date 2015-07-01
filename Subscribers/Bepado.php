<?php

namespace Shopware\Bepado\Subscribers;

/**
 * Class Bepado
 * @package Shopware\Bepado\Subscribers
 */
class Bepado extends BaseSubscriber
{
    public function getSubscribedEvents()
    {
        return array(
            'Enlight_Controller_Action_PostDispatch_Backend_Index' => 'injectBackendBepadoMenuEntry',

        );
    }

    /**
     * Callback method for the Backend/Index postDispatch event.
     * Will add the bepado sprite to the menu
     *
     * @event Enlight_Controller_Action_PostDispatch_Backend_Index
     * @param \Enlight_Event_EventArgs $args
     * @returns boolean|void
     */
    public function injectBackendBepadoMenuEntry(\Enlight_Event_EventArgs $args)
    {
        /** @var $action \Enlight_Controller_Action */
        $action = $args->getSubject();
        $request = $action->Request();
        $response = $action->Response();
        $view = $action->View();

        if (!$request->isDispatched() || $response->isException() || !$view->hasTemplate()
        ) {
            return;
        }

        //TODO: make marketplaceName dynamic
        $view->marketplaceName = 'semdemo';
        $view->marketplaceNetworkUrl = 'http://semdemo.stage.bepado.de';

        $view->addTemplateDir($this->Path() . 'Views/');
        $view->extendsTemplate('backend/bepado/menu_entry.tpl');
    }

}