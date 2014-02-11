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
     * Disable authentication and JSon renderer
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

    /**
     * Main bepado interface
     *
     * @throws Exception
     */
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
        } catch (\Exception $e) {
            // Always write errors to the log
            $this->writeLog(true, $request, $this->formatException($e));
            throw $e;
        }

        if ($loggingEnabled) {
            $this->writeLog(false, $request, $result);
        }

        echo $result;
    }

    /**
     * Write the log
     *
     * @param $isError
     * @param $request
     * @param $response
     */
    public function writeLog($isError, $request, $response)
    {        
        try {            
            $document = simplexml_load_string($request);
            $service = $document->service;
            $command = $document->command;
        } catch(\Exception $e) {
            $service = 'general';
            $command = 'error';
        }

        Shopware()->Db()->query('
            INSERT INTO `s_plugin_bepado_log`
            (`isError`, `service`, `command`, `request`, `response`, `time`)
            VALUES (?, ?, ?, ?, ?, NOW())
        ', array($isError, $service, $command, $request, $response));
        
        // Cleanup after 3 days
        Shopware()->Db()->exec('DELETE FROM `s_plugin_bepado_log`  WHERE DATE_SUB(CURDATE(),INTERVAL 3 DAY) >= time');
    }

    /**
     * Format a given exception for the log
     *
     * @param Exception $e
     * @return string
     */
    public function formatException(\Exception $e)
    {
        return sprintf(
            "%s \n\n %s \n\n %s",
            $e->getMessage(),
            $e->getFile() . ': ' . $e->getLine(),
            $e->getTraceAsString()
        );
    }
}