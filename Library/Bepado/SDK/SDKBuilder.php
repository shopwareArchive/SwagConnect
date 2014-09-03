<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Bepado\SDK;

use PDO;

/**
 * Builder object for the Bepado SDK.
 */
class SDKBuilder
{
    /**
     * @var string
     */
    private $apiKey;

    /**
     * @var string
     */
    private $apiEndpointUrl;

    /**
     * @var \Bepado\SDK\Gateway
     */
    private $gateway;

    /**
     * @var \Bepado\SDK\ProductFromShop
     */
    private $productFromShop;

    /**
     * @var \Bepado\SDK\ProductToShop
     */
    private $productToShop;

    /**
     * @var \Bepado\SDK\ErrorHandler
     */
    private $errorHandler;

    /**
     * @var string
     */
    private $softwareVersion;

    /** @var  \Bepado\SDK\ProductPayments */
    private $productPayments;

    /**
     * @param string $apiKey
     * @return \Bepado\SDK\SDKBuilder
     */
    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;

        return $this;
    }

    /**
     * @param string $apiEndpointUrl
     * @return \Bepado\SDK\SDKBuilder
     */
    public function setApiEndpointUrl($apiEndpointUrl)
    {
        $this->apiEndpointUrl = $apiEndpointUrl;

        return $this;
    }

    /**
     * @param \PDO $connection
     * @return \Bepado\SDK\SDKBuilder
     */
    public function configurePDOGateway(PDO $connection)
    {
        $this->gateway = new Gateway\PDO($connection);

        return $this;
    }

    /**
     * @param string $host
     * @param string $username
     * @param string $passwd
     * @param string $dbname
     * @param string $port
     * @param string $socket
     * @return \Bepado\SDK\SDKBuilder
     */
    public function configureMySQLiGateway(
        $host = null,
        $username = null,
        $passwd = null,
        $dbname = null,
        $port = null,
        $socket = null
    ) {
        $this->gateway = new Gateway\MySQLi(
            $host, $username, $passwd, $dbname, $port, $socket
        );

        return $this;
    }

    /**
     * @return \Bepado\SDK\SDKBuilder
     */
    public function setGateway(Gateway $gateway)
    {
        $this->gateway = $gateway;

        return $this;
    }

    /**
     * @param \Bepado\SDK\ProductToShop
     * @return \Bepado\SDK\SDKBuilder
     */
    public function setProductToShop(ProductToShop $productToShop)
    {
        $this->productToShop = $productToShop;

        return $this;
    }

    /**
     * @param \Bepado\SDK\ProductFromShop
     * @return \Bepado\SDK\SDKBuilder
     */
    public function setProductFromShop(ProductFromShop $productFromShop)
    {
        $this->productFromShop = $productFromShop;

        return $this;
    }

    /**
     * @param \Bepado\SDK\ErrorHandler
     * @return \Bepado\SDK\SDKBuilder
     */
    public function setErrorHandler(ErrorHandler $errorHandler)
    {
        $this->errorHandler = $errorHandler;

        return $this;
    }

    /**
     * @param string $pluginSoftwareVersion
     */
    public function setPluginSoftwareVersion($softwareVersion)
    {
        $this->softwareVersion = $softwareVersion;

        return $this;
    }

    /**
     * @param mixed $productPayments
     */
    public function setProductPayments(ProductPayments $productPayments)
    {
        $this->productPayments = $productPayments;
    }

    /**
     * @return mixed
     */
    public function getProductPayments()
    {
        return $this->productPayments;
    }

    /**
     * Create the SDK instance.
     *
     * @return \Bepado\SDK\SDK
     */
    public function build()
    {
        if (!$this->apiKey ||
            !$this->apiEndpointUrl ||
            !$this->gateway ||
            !$this->productToShop ||
            !$this->productFromShop ||
            !$this->productPayments) {

            throw new \RuntimeException("Missing required argument for building SDK.");
        }

        return new SDK(
            $this->apiKey,
            $this->apiEndpointUrl,
            $this->gateway,
            $this->productToShop,
            $this->productFromShop,
            $this->errorHandler,
            null,
            $this->softwareVersion,
            $this->productPayments
        );
    }
}
