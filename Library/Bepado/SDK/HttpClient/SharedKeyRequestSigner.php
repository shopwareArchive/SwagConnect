<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * @version $Revision$
 */

namespace Bepado\SDK\HttpClient;

use Bepado\SDK\Gateway\ShopConfiguration;
use Bepado\SDK\Service\Clock;
use Bepado\SDK\Struct\AuthenticationToken;

class SharedKeyRequestSigner implements RequestSigner
{
    /**
     * @param ShopConfiguration
     */
    private $gateway;

    /**
     * @param Clock
     */
    private $clock;

    /**
     * @var string
     */
    private $apiKey;

    public function __construct(ShopConfiguration $gateway, Clock $clock, $apiKey)
    {
        $this->gateway = $gateway;
        $this->clock = $clock;
        $this->apiKey = $apiKey;
    }

    /**
     * Return array of headers required to sign particular request.
     *
     * @param int $shopId
     * @param string $body
     * @return array
     */
    public function signRequest($shopId, $body)
    {
        $configuration   = $this->gateway->getShopConfiguration($shopId);
        $verificationKey = $configuration->key;
        $myShopId        = $this->gateway->getShopId();
        $requestDate     = gmdate('D, d M Y H:i:s', $this->clock->time()) . ' GMT';
        $nonce           = $this->generateNonce($requestDate, $body, $verificationKey);

        return array(
            'Authorization: SharedKey party="' . $myShopId . '",nonce="' . $nonce . '"',
            'Date: ' . $requestDate
        );
    }

    /**
     * Verify that a given message is valid.
     *
     * @param string $body
     * @param array $headers
     * @return bool
     */
    public function verifyRequest($body, array $headers)
    {
        if (!isset($headers['HTTP_AUTHORIZATION']) || !isset($headers['HTTP_DATE'])) {
            return new AuthenticationToken(array('authenticated' => false));
        }

        $currentDate = time();

        list($type, $params) = explode(" ", $headers['HTTP_AUTHORIZATION'], 2);

        if ($type !== "SharedKey") {
            return new AuthenticationToken(array('authenticated' => false));
        }

        if (preg_match('(^(party="([^"]+)\",nonce="([^"]+)")$)', $params, $matches)) {
            $party = $matches[2];
            $actualNonce = $matches[3];

            if ($party === "bepado") {
                $verificationKey = $this->apiKey;
            } elseif (is_numeric($party)) {
                $configuration = $this->gateway->getShopConfiguration($party);
                $verificationKey = $configuration->key;
                $party = (int)$party;
            } else {
                return new AuthenticationToken(array('authenticated' => false));
            }

            $expectedNonce = $this->generateNonce($headers['HTTP_DATE'], $body, $verificationKey);

            if ($this->stringsEqual($actualNonce, $expectedNonce)) {
                return new AuthenticationToken(array('authenticated' => true, 'userIdentifier' => $party));
            }
        }

        return new AuthenticationToken(array('authenticated' => false));
    }

    private function generateNonce($requestDate, $body, $key)
    {
        return hash_hmac('sha512', $requestDate . "\n" . $body, $key);
    }

    /**
     * Constant time string comparison to prevent timing attacks.
     *
     * @param string $a
     * @param string $b
     * @return bool
     */
    private function stringsEqual($a, $b)
    {
        if (strlen($a) !== strlen($b)) {
            // returning early is valid, because we compare hashes an attacker does not gain information through this
            return false;
        }

        $result = 0;

        for ($i = 0; $i < strlen($a); $i++) {
            $result |= ord($a[$i]) ^ ord($b[$i]);
        }

        return 0 === $result;
    }
}
