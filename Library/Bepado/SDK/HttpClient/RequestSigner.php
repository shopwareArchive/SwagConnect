<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * @version 1.0.129
 */

namespace Bepado\SDK\HttpClient;

/**
 * Sign and Verify Requests from and to the SDK.
 */
interface RequestSigner
{
    /**
     * Return array of headers required to sign particular request.
     *
     * @param int $shopId
     * @param string $body
     * @return array
     */
    public function signRequest($shopId, $body);

    /**
     * Verify that a given message is valid.
     *
     * @param string $body
     * @param array $headers
     * @return \Bepado\SDK\Struct\AuthenticationToken
     */
    public function verifyRequest($body, array $headers);
}
