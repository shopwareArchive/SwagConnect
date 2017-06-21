<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Controllers\Backend;

use ShopwarePlugins\Connect\Components\Logger;

/**
 * @category  Shopware
 *
 * @copyright Copyright (c) 2013, shopware AG (http://www.shopware.de)
 * @author    Heiner Lohaus
 */
class ConnectGatewayBaseController extends \Enlight_Controller_Action
{
    private $configComponent;

    /**
     * Disable authentication and JSon renderer
     */
    public function init()
    {
        Shopware()->Plugins()->Backend()->Auth()->setNoAuth();
        Shopware()->Plugins()->Controller()->ViewRenderer()->setNoRender();
    }

    /**
     * @return Logger
     */
    public function getLogger()
    {
        $loggingEnabled = $this->getConfigComponent()->getConfig('logRequest');

        return new Logger(Shopware()->Db(), $loggingEnabled);
    }

    /**
     * @return \Shopware\Connect\SDK
     */
    public function getSDK()
    {
        return Shopware()->Container()->get('ConnectSDK');
    }

    public function getRestApiRequest()
    {
        return Shopware()->Container()->get('swagconnect.rest_api_request');
    }

    public function getPluginManager()
    {
        return Shopware()->Container()->get('shopware_plugininstaller.plugin_manager');
    }

    public function getConfigComponent()
    {
        if (!$this->configComponent) {
            $this->configComponent = new \ShopwarePlugins\Connect\Components\Config(Shopware()->Models());
        }

        return $this->configComponent;
    }

    /**
     * Main connect interface
     *
     * @throws \Exception
     */
    public function indexAction()
    {
        $this->Response()->setHeader('Content-Type', 'text/xml; charset=utf-8');

        $request = file_get_contents('php://input');
        $logger = $this->getLogger();

        try {
            $this->get('events')->notify(
                'Shopware_Connect_SDK_Handle_Before',
                [$request]
            );

            $sdk = $this->getSDK();
            $result = $sdk->handle(
                $request
            );

            $this->get('events')->notify(
                'Shopware_Connect_SDK_Handle_After',
                [$request, $result]
            );
        } catch (\Exception $e) {
            // Always write errors to the log
            $logger->write(true, $request, $e);
            $logger->write(true, 'Headers: ' . print_r($_SERVER, true), $e, 'request-headers');
            throw $e;
        }

        $logger->write(false, $request, $result);

        echo $result;
    }

    public function removePluginAction()
    {
        $this->Response()->setHeader('Content-Type', 'application/json; charset=utf-8');

        $request = file_get_contents('php://input');

        $apiRequest = $this->getRestApiRequest();
        $result = $apiRequest->verifyRequest($request);

        if ($result->isOk()) {
            /** @var \Shopware\Bundle\PluginInstallerBundle\Service\InstallerService $pluginManager */
            $pluginManager = $this->getPluginManager();
            $plugin = $pluginManager->getPluginByName('SwagConnect');
            $pluginManager->uninstallPlugin($plugin);
        }

        echo $result->getContent();
    }
}
