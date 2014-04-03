<?php

namespace Shopware\Bepado\Components;

/**
 * Logger for bepadoGateway operations
 *
 * Class Logger
 * @package Shopware\Bepado\Components
 */
class Logger
{

    /** @var  \PDO|\Enlight_Components_Db_Adapter_Pdo_Mysql */
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
    public function write($isError, $request, $response, $custom=null)
    {
        if ($response instanceof \Exception) {
            $this->formatException($response);
        }

        $service='general';
        $command = 'custom-error';
        if ($custom) {
            $command = $command . '-' . $custom;
        }

        if ($request && !$custom) {
            $document = simplexml_load_string($request);
            if ($document)  {
                $service = $document->service;
                $command = $document->command;
            }
        }

        if (!$this->enabled && !in_array($command, array('checkProducts', 'reserveProducts', 'buy'))) {
            return;
        }

        $this->db->query('
            INSERT INTO `s_plugin_bepado_log`
            (`isError`, `service`, `command`, `request`, `response`, `time`)
            VALUES (?, ?, ?, ?, ?, NOW())
        ', array($isError, $service, $command, $request, $response));

        $this->cleanup();
    }

    /**
     * Format a given exception for the log
     *
     * @param \Exception $e
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
        $this->db->exec('DELETE FROM `s_plugin_bepado_log`  WHERE DATE_SUB(CURDATE(),INTERVAL 1 DAY) >= time');
    }
}