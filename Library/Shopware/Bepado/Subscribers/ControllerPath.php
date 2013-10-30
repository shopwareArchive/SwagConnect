<?php

namespace Shopware\Bepado\Subscribers;

class ControllerPath extends BaseSubscriber
{
    public function getSubscribedEvents()
    {
        return array(
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_BepadoGateway' => 'onGetControllerPathGateway',
            'Enlight_Controller_Dispatcher_ControllerPath_Frontend_Bepado' => 'onGetControllerPathFrontend',
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_Bepado' => 'onGetControllerPathBackend'
        );
    }


    /**
     * Register the bepado backend controller
     *
     * @param   \Enlight_Event_EventArgs $args
     * @return  string
     * @Enlight\Event Enlight_Controller_Dispatcher_ControllerPath_Backend_Bepado
     */
    public function onGetControllerPathBackend(\Enlight_Event_EventArgs $args)
    {
        $this->registerMyTemplateDir();
        $this->registerMySnippets();
        return $this->Path() . 'Controllers/Backend/Bepado.php';
    }

    /**
     * Register the bepadoGateway backend controller
     *
     * @param   \Enlight_Event_EventArgs $args
     * @return  string
     */
    public function onGetControllerPathGateway(\Enlight_Event_EventArgs $args)
    {
        return $this->Path() . 'Controllers/Backend/BepadoGateway.php';
    }

    /**
     * Register the bepado frontend controller
     *
     * @param   \Enlight_Event_EventArgs $args
     * @return  string
     */
    public function onGetControllerPathFrontend(\Enlight_Event_EventArgs $args)
    {
        $this->registerMyTemplateDir();
        return $this->Path() . 'Controllers/Frontend/Bepado.php';
    }
}