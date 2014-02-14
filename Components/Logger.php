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

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Write the log
     *
     * @param $isError
     * @param $request
     * @param $response
     */
    public function write($isError, $request, $response)
    {
        if ($response instanceof \Exception) {
            $this->formatException($response);
        }

        $service = 'general';
        $command = 'error';

        if ($request) {
            $document = simplexml_load_string($request);
            if ($document)  {
                $service = $document->service;
                $command = $document->command;
            }
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