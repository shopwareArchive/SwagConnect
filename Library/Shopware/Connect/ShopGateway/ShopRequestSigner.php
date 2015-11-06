<?php

namespace Shopware\Connect\ShopGateway;

use Shopware\Connect\HttpClient\RequestSigner;

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
     * @var Shopware\Connect\HttpClient\RequestSigner
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
