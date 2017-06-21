<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Components;

/**
 * Logger for connectGateway operations
 *
 * Class Logger
 */
class Logger
{
    /** @var \PDO|\Enlight_Components_Db_Adapter_Pdo_Mysql */
    protected $db;

    protected $enabled;

    public function __construct($db, $enabled = true)
    {
        $this->db = $db;
        $this->enabled = $enabled;
    }

    /**
     * Write the log
     *
     * @param $isError
     * @param $request
     * @param $response
     * @param $custom
     */
    public function write($isError, $request, $response, $custom = null)
    {
        if ($response instanceof \Exception) {
            $this->formatException($response);
        }

        $service = 'general';
        $command = 'custom-error';
        if ($custom) {
            $command = $command . '-' . $custom;
        }

        if ($request && !$custom) {
            $document = simplexml_load_string($request);
            if ($document) {
                $service = $document->service;
                $command = $document->command;
            }
        }

        if (!$this->enabled && !in_array($command, ['checkProducts', 'reserveProducts', 'buy'])) {
            return;
        }

        $this->db->query('
            INSERT INTO `s_plugin_connect_log`
            (`isError`, `service`, `command`, `request`, `response`, `time`)
            VALUES (?, ?, ?, ?, ?, NOW())
        ', [$isError, $service, $command, $request, $response]);

        $this->cleanup();
    }

    /**
     * Format a given exception for the log
     *
     * @param \Exception $e
     *
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

    /**
     * Purge the log after one day
     */
    protected function cleanup()
    {
        $this->db->exec('DELETE FROM `s_plugin_connect_log`  WHERE DATE_SUB(CURDATE(),INTERVAL 1 DAY) >= time');
    }
}
