<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Subscribers;

use Enlight\Event\SubscriberInterface;

class ControllerPath implements SubscriberInterface
{
    /**
     * @var string
     */
    private $pluginPath;

    /**
     * @param string $pluginPath
     */
    public function __construct($pluginPath)
    {
        $this->pluginPath = $pluginPath;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_ConnectGateway' => 'onGetControllerPathGateway',
            'Enlight_Controller_Dispatcher_ControllerPath_Frontend_Connect' => 'onGetControllerPathFrontend',
            'Enlight_Controller_Dispatcher_ControllerPath_Frontend_ConnectProductGateway' => 'onGetControllerPathFrontendConnectControllerGateway',
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_Connect' => 'onGetControllerPathBackend',
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_LastChanges' => 'onGetLastChangesControllerPath',
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_ConnectConfig' => 'onGetControllerPathConnectConfig',
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_Import' => 'onGetControllerPathImport'
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
        return $this->pluginPath . '/Controllers/Backend/Connect.php';
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
        return $this->pluginPath . '/Controllers/Backend/LastChanges.php';
    }

    /**
     * Register the connectGateway backend controller
     *
     * @param   \Enlight_Event_EventArgs $args
     * @return  string
     */
    public function onGetControllerPathGateway(\Enlight_Event_EventArgs $args)
    {
        return $this->pluginPath . '/Controllers/Backend/ConnectGateway.php';
    }

    /**
     * Register the connect frontend controller
     *
     * @param   \Enlight_Event_EventArgs $args
     * @return  string
     */
    public function onGetControllerPathFrontend(\Enlight_Event_EventArgs $args)
    {
        return $this->pluginPath . '/Controllers/Frontend/Connect.php';
    }

    /**
     * Register the connect product frontend controller
     *
     * @param   \Enlight_Event_EventArgs $args
     * @return  string
     */
    public function onGetControllerPathFrontendConnectControllerGateway(\Enlight_Event_EventArgs $args)
    {
        return $this->pluginPath . '/Controllers/Frontend/ConnectProductGateway.php';
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
        return $this->pluginPath . '/Controllers/Backend/ConnectConfig.php';
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
        return $this->pluginPath . '/Controllers/Backend/Import.php';
    }
}
