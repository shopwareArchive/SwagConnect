<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * @version $Revision$
 */

namespace Bepado\SDK;

/**
 * HTTP client implementation
 *
 * @version $Revision$
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
