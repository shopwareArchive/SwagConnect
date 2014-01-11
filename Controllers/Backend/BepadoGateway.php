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

/**
 * @category  Shopware
 * @package   Shopware\Plugins\SwagBepado
 * @copyright Copyright (c) 2013, shopware AG (http://www.shopware.de)
 * @author    Heiner Lohaus
 */
class Shopware_Controllers_Backend_BepadoGateway extends Enlight_Controller_Action
{
    /**
     * Init controller method
     */
    public function init()
    {
        Shopware()->Plugins()->Backend()->Auth()->setNoAuth();
        Shopware()->Plugins()->Controller()->ViewRenderer()->setNoRender();
    }

    /**
     * @return \Bepado\SDK\SDK
     */
    public function getSDK()
    {
        return Shopware()->Bootstrap()->getResource('BepadoSDK');
    }

    public function indexAction()
    {
        $this->Response()->setHeader('Content-Type', 'text/xml; charset=utf-8');

        $loggingEnabled = Shopware()->Config()->getByNamespace('SwagBepado', 'logRequest');

        $request = file_get_contents('php://input');

        try {
            $sdk = $this->getSDK();
            $result = $sdk->handle(
                $request
            );
        } catch (Exception $e) {
            if ($loggingEnabled) {
                $this->writeLog(true, $request, $e->getMessage() . "\n\n" . $e->getTraceAsString());
            }
            throw $e;
        }

        if ($loggingEnabled) {
            $this->writeLog(false, $request, $result);
        }

        echo $result;
    }

    public function writeLog($isError, $request, $response)
    {
        $document = simplexml_load_string($request);
        $service = $document->service;
        $command = $document->command;

        Shopware()->Db()->query('
            INSERT INTO `s_plugin_bepado_log`
            (`isError`, `service`, `command`, `request`, `response`, `time`)
            VALUES (?, ?, ?, ?, ?, NOW())
        ', array($isError, $service, $command, $request, $response));
    }
}