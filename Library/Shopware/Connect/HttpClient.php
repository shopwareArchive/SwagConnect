<?php
/**
 * This file is part of the Shopware Connect SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Shopware\Connect;

/**
 * HTTP client implementation
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */
abstract class HttpClient
{
    /**
     * Execute a HTTP request to the remote server
     *
     * @param string $method
     * @param string $path
     * @param mixed $body
     * @param array $headers
     * @return HttpClient\Reponse
     */
    abstract public function request($method, $path, $body = null, array $headers = array());
}
