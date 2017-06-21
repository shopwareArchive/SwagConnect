<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
     */
    public function handleError(Error $error)
    {
        $this->logger->write(true, null, sprintf(
                "%s \n\n %s",
                $error->message,
                $error->debugText
            )
        );
    }

    /**
     * Handle exception
     *
     * @param \Exception $exception
     *
     * @throws \Exception
     */
    public function handleException(\Exception $exception)
    {
        $this->logger->write(true, null, $exception);

        throw $exception;
    }
}
