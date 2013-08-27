<?php

namespace Bepado\SDK\ShopGateway;

use Bepado\SDK\HttpClient\RequestSigner;

/**
 * Partially applied request signer, knows about the shop id already.
 */
class ShopRequestSigner
{
    /**
     * @var int
     */
    private $shopId;

    /**
     * @var Bepado\SDK\HttpClient\RequestSigner
     */
    private $requestSigner;

    public function __construct(RequestSigner $requestSigner, $shopId)
    {
        $this->requestSigner = $requestSigner;
        $this->shopId = $shopId;
    }

    /**
     * Sign Request Body with the known ShopId and return HTTP headers.
     *
     * @param string $body
     * @return array
     */
    public function signRequest($body)
    {
        return $this->requestSigner->signRequest($this->shopId, $body);
    }
}
