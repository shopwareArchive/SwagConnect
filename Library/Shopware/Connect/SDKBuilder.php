<?php
/**
 * This file is part of the Shopware Connect SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Shopware\Connect;

use PDO;

/**
 * Builder object for the Shopware Connect SDK.
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
     * @var \Shopware\Connect\Gateway
     */
    private $gateway;

    /**
     * @var \Shopware\Connect\ProductFromShop
     */
    private $productFromShop;

    /**
     * @var \Shopware\Connect\ProductToShop
     */
    private $productToShop;

    /**
     * @var \Shopware\Connect\ErrorHandler
     */
    private $errorHandler;

    /**
     * @var string
     */
    private $softwareVersion;

    /**
     * @param string $apiKey
     * @return \Shopware\Connect\SDKBuilder
     */
    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;

        return $this;
    }

    /**
     * @param string $apiEndpointUrl
     * @return \Shopware\Connect\SDKBuilder
     */
    public function setApiEndpointUrl($apiEndpointUrl)
    {
        $this->apiEndpointUrl = $apiEndpointUrl;

        return $this;
    }

    /**
     * @param \PDO $connection
     * @return \Shopware\Connect\SDKBuilder
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
     * @return \Shopware\Connect\SDKBuilder
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
     * @return \Shopware\Connect\SDKBuilder
     */
    public function setGateway(Gateway $gateway)
    {
        $this->gateway = $gateway;

        return $this;
    }

    /**
     * @param \Shopware\Connect\ProductToShop
     * @return \Shopware\Connect\SDKBuilder
     */
    public function setProductToShop(ProductToShop $productToShop)
    {
        $this->productToShop = $productToShop;

        return $this;
    }

    /**
     * @param \Shopware\Connect\ProductFromShop
     * @return \Shopware\Connect\SDKBuilder
     */
    public function setProductFromShop(ProductFromShop $productFromShop)
    {
        $this->productFromShop = $productFromShop;

        return $this;
    }

    /**
     * @param \Shopware\Connect\ErrorHandler
     * @return \Shopware\Connect\SDKBuilder
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
     * @return mixed
     */
    public function getProductPayments()
    {
        return $this->productPayments;
    }

    /**
     * Create the SDK instance.
     *
     * @return \Shopware\Connect\SDK
     */
    public function build()
    {
        if (!$this->apiKey ||
            !$this->apiEndpointUrl ||
            !$this->gateway ||
            !$this->productToShop ||
            !$this->productFromShop) {

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
            $this->softwareVersion
        );
    }
}
