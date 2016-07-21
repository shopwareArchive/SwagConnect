<?php

namespace ShopwarePlugins\Connect\Components;

use Shopware\Connect\ErrorHandler;
use Shopware\Connect\Struct\Error;

class ShopwareErrorHandler extends ErrorHandler
{
    /**
     * @var \ShopwarePlugins\Connect\Components\Logger
     */
    private $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Handle error
     *
     * @param Error $error
     * @return void
     */
    public function handleError(Error $error)
    {
        $this->logger->write(true, null, $error);
    }

    /**
     * Handle exception
     *
     * @param \Exception $exception
     * @throws \Exception
     * @return void
     */
    public function handleException(\Exception $exception)
    {
        $this->logger->write(true, null, $exception);

        throw $exception;
    }

}