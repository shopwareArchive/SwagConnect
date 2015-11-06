<?php
/**
 * This file is part of the Shopware Connect SDK component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Shopware\Connect\Rpc;

/**
 * Handle PHP errors and notices gracefully to allow
 */
abstract class ErrorHandler
{
    private $oldExceptionHandler;

    public function registerHandlers()
    {
        if (defined("PHPUNIT")) {
            // Do not overwrite error handlers in PHPUnit tests. Can cause
            // silent aborts.
            return;
        }

        ini_set('display_errors', false);
        $this->oldExceptionHandler = set_exception_handler(array($this, 'handleException'));

        register_shutdown_function(array($this, 'handleShutdown'));
    }

    public function restore()
    {
        set_exception_handler($this->oldExceptionHandler);
        $this->oldErrorHandler = $this->oldExceptionHandler = null;
    }

    public function handleShutdown()
    {
        $error = error_get_last();

        if ($error === NULL) {
            return;
        }

        if (($error['type'] & (E_ERROR|E_PARSE)) === 0) {
            return;
        }

        $error = "{$error['message']} in {$error['file']}+{$error['line']}";

        $this->renderResponse($error);
        exit(1);
    }

    public function handleException($e)
    {
        $this->renderResponse($e->getMessage());
        exit(1);
    }

    abstract public function renderResponse($message);
}
