<?php

namespace Shopware\Bepado\Subscribers;

/**
 * Register some controllers
 *
 * Class ControllerPath
 * @package Shopware\Bepado\Components\Subscribers
 */
class ControllerPath extends BaseSubscriber
{
    public function getSubscribedEvents()
    {
        return array(
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_BepadoGateway' => 'onGetControllerPathGateway',
            'Enlight_Controller_Dispatcher_ControllerPath_Frontend_Bepado' => 'onGetControllerPathFrontend',
            'Enlight_Controller_Dispatcher_ControllerPath_Frontend_BepadoProductGateway' => 'onGetControllerPathFrontendBepadoControllerGateway',
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_Bepado' => 'onGetControllerPathBackend',
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_BepadoConfig' => 'onGetControllerPathBepadoConfig'
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

    /**
     * Register the bepado product frontend controller
     *
     * @param   \Enlight_Event_EventArgs $args
     * @return  string
     */
    public function onGetControllerPathFrontendBepadoControllerGateway(\Enlight_Event_EventArgs $args)
    {
        $this->registerMyTemplateDir();
        return $this->Path() . 'Controllers/Frontend/BepadoProductGateway.php';
    }

    /**
     * Register the bepado config backend controller
     *
     * @param   \Enlight_Event_EventArgs $args
     * @return  string
     * @Enlight\Event Enlight_Controller_Dispatcher_ControllerPath_Backend_BepadoConfig
     */
    public function onGetControllerPathBepadoConfig(\Enlight_Event_EventArgs $args)
    {
        $this->registerMyTemplateDir();
        $this->registerMySnippets();
        return $this->Path() . 'Controllers/Backend/BepadoConfig.php';
    }
}