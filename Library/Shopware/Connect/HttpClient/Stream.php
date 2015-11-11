<?php
/**
 * This file is part of the Shopware Connect SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Shopware\Connect\HttpClient;

use Shopware\Connect\HttpClient;

/**
 * HTTP client implementation
 *
 * The constructor of this class expects the remote REST server as argument.
 * This includes host/ip, port and protocol.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */
class Stream extends HttpClient
{
    /**
     * Optional default headers for each request.
     *
     * @var array
     */
    private $headers = array();

    /**
     * The remote REST server location.
     *
     * @var string
     */
    private $server;

    /**
     * Constructs a new REST client instance for the given <b>$server</b>.
     *
     * @param string $server Remote server location. Must include the used protocol.
     */
    public function __construct($server)
    {
        $this->server = $server;
    }

    /**
     * Add default headers
     *
     * @param array $headers
     * @return void
     */
    public function addDefaultHeaders(array $headers)
    {
        $this->headers = array_merge(
            $this->headers,
            $headers
        );
    }

    /**
     * Execute a HTTP request to the remote server
     *
     * @param string $method
     * @param string $path
     * @param mixed $body
     * @param array $headers
     * @return Reponse
     */
    public function request($method, $path, $body = null, array $headers = array())
    {
        $httpFilePointer = @fopen(
            $this->server . $path,
            'r',
            false,
            stream_context_create(
                array(
                    'http' => array(
                        'method'        => $method,
                        'content'       => $body,
                        'ignore_errors' => true,
                        'header'        => implode(
                            "\r\n",
                            array_merge(
                                $this->headers,
                                $headers
                            )
                        ),
                    ),
                )
            )
        );

        if ($httpFilePointer === false) {
            $error = error_get_last();
            throw new \RuntimeException(
                "Could not connect to server {$this->server}: " . $error['message']
            );
        }

        $response = new HttpClient\Response();
        while (!feof($httpFilePointer)) {
            $response->body .= fgets($httpFilePointer);
        }

        $metaData   = stream_get_meta_data($httpFilePointer);
        $rawHeaders = isset($metaData['wrapper_data']['headers']) ?
            $metaData['wrapper_data']['headers'] :
            $metaData['wrapper_data'];

        foreach ($rawHeaders as $lineContent) {
            if (preg_match('(^HTTP/(?P<version>\d+\.\d+)\s+(?P<status>\d+))S', $lineContent, $match)) {
                $response->status = (int) $match['status'];
            } else {
                list($key, $value) = explode(':', $lineContent, 2);
                $response->headers[strtolower($key)] = ltrim($value);
            }
        }

        return $response;
    }
}
