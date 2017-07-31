<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Subscribers;

/**
 * Register some controllers
 *
 * Class ControllerPath
 * @package ShopwarePlugins\Connect\Components\Subscribers
 */
class ControllerPath extends BaseSubscriber
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @return array
     */
    public function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_ConnectGateway' => 'onGetControllerPathGateway',
            'Enlight_Controller_Dispatcher_ControllerPath_Frontend_Connect' => 'onGetControllerPathFrontend',
            'Enlight_Controller_Dispatcher_ControllerPath_Frontend_ConnectProductGateway' => 'onGetControllerPathFrontendConnectControllerGateway',
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_Connect' => 'onGetControllerPathBackend',
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_LastChanges' => 'onGetLastChangesControllerPath',
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_ConnectConfig' => 'onGetControllerPathConnectConfig',
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_Import' => 'onGetControllerPathImport',
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_ShippingGroups' => 'onGetControllerPathShippingGroups'
        ];
    }

    /**
     * Register the connect backend controller
     *
     * @param   \Enlight_Event_EventArgs $args
     * @return  string
     * @Enlight\Event Enlight_Controller_Dispatcher_ControllerPath_Backend_Connect
     */
    public function onGetControllerPathBackend(\Enlight_Event_EventArgs $args)
    {
        $this->registerMyTemplateDir();
        $this->registerMySnippets();

        return $this->Path() . 'Controllers/Backend/Connect.php';
    }

    /**
     * Register the connect backend controller
     *
     * @param   \Enlight_Event_EventArgs $args
     * @return  string
     * @Enlight\Event Enlight_Controller_Dispatcher_ControllerPath_Backend_Connect
     */
    public function onGetLastChangesControllerPath(\Enlight_Event_EventArgs $args)
    {
        $this->registerMyTemplateDir();
        $this->registerMySnippets();

        return $this->Path() . 'Controllers/Backend/LastChanges.php';
    }

    /**
     * Register the connectGateway backend controller
     *
     * @param   \Enlight_Event_EventArgs $args
     * @return  string
     */
    public function onGetControllerPathGateway(\Enlight_Event_EventArgs $args)
    {
        return $this->Path() . '/Controllers/Backend/ConnectGateway.php';
    }

    /**
     * Register the connect frontend controller
     *
     * @param   \Enlight_Event_EventArgs $args
     * @return  string
     */
    public function onGetControllerPathFrontend(\Enlight_Event_EventArgs $args)
    {
        $this->registerMyTemplateDir();

        return $this->Path() . 'Controllers/Frontend/Connect.php';
    }

    /**
     * Register the connect product frontend controller
     *
     * @param   \Enlight_Event_EventArgs $args
     * @return  string
     */
    public function onGetControllerPathFrontendConnectControllerGateway(\Enlight_Event_EventArgs $args)
    {
        $this->registerMyTemplateDir();

        return $this->Path() . 'Controllers/Frontend/ConnectProductGateway.php';
    }

    /**
     * Register the connect config backend controller
     *
     * @param   \Enlight_Event_EventArgs $args
     * @return  string
     * @Enlight\Event Enlight_Controller_Dispatcher_ControllerPath_Backend_ConnectConfig
     */
    public function onGetControllerPathConnectConfig(\Enlight_Event_EventArgs $args)
    {
        $this->registerMyTemplateDir();
        $this->registerMySnippets();

        return $this->Path() . 'Controllers/Backend/ConnectConfig.php';
    }

    /**
     * Register the connect import backend controller
     *
     * @param   \Enlight_Event_EventArgs $args
     * @return  string
     * @Enlight\Event Enlight_Controller_Dispatcher_ControllerPath_Backend_Import
     */
    public function onGetControllerPathImport(\Enlight_Event_EventArgs $args)
    {
        return $this->Path() . 'Controllers/Backend/Import.php';
    }

    public function onGetControllerPathShippingGroups(\Enlight_Event_EventArgs $args)
    {
        $this->registerMyTemplateDir();
        $this->registerMySnippets();

        return $this->Path() . 'Controllers/Backend/ShippingGroups.php';
    }
}
