<?php
/**
 * Shopware 4.0
 * Copyright © 2013 shopware AG
 *
 * According to our dual licensing model, this program can be used either
 * under the terms of the GNU Affero General Public License, version 3,
 * or under a proprietary license.
 *
 * The texts of the GNU Affero General Public License with an additional
 * permission and of our proprietary license can be found at and
 * in the LICENSE file you have received along with this program.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * "Shopware" is a registered trademark of shopware AG.
 * The licensing of the program under the AGPLv3 does not imply a
 * trademark license. Therefore any rights, title and interest in
 * our trademarks remain entirely with us.
 */

use Shopware\Bepado\Components\Logger;

/**
 * @category  Shopware
 * @package   Shopware\Plugins\SwagBepado
 * @copyright Copyright (c) 2013, shopware AG (http://www.shopware.de)
 * @author    Heiner Lohaus
 */
class Shopware_Controllers_Backend_BepadoGateway extends Enlight_Controller_Action
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
        return new Logger(Shopware()->Db());
    }

    /**
     * @return \Bepado\SDK\SDK
     */
    public function getSDK()
    {
        return Shopware()->Bootstrap()->getResource('BepadoSDK');
    }

    public function getConfigComponent()
    {
        if (!$this->configComponent) {
            $this->configComponent = new \Shopware\Bepado\Components\Config(Shopware()->Models());
        }

        return $this->configComponent;
    }

    /**
     * Main bepado interface
     *
     * @throws Exception
     */
    public function indexAction()
    {
        $this->Response()->setHeader('Content-Type', 'text/xml; charset=utf-8');

        $loggingEnabled = $this->getConfigComponent()->getConfig('logRequest');

        $logger = $this->getLogger();

        $request = file_get_contents('php://input');

        try {
            $sdk = $this->getSDK();
            $result = $sdk->handle(
                $request
            );
        } catch (\Exception $e) {
            // Always write errors to the log
            $logger->write(true, $request, $e);
            $logger->write(true, 'Headers: '.print_r($_SERVER, true), $e, 'request-headers');
            throw $e;
        }

        if ($loggingEnabled) {
            $logger->write(false, $request, $result);
        }

        echo $result;
    }
}