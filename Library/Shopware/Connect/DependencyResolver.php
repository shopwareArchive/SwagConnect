<?php
/**
 * This file is part of the Shopware Connect SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Shopware\Connect;

use Shopware\Connect\Rpc;
use Shopware\Connect\Struct\RpcCall;

/**
 * SDK Dependency Resolver
 *
 * Resolves the dependencies of the SDK components.
 */
class DependencyResolver
{
    /**
     * @var string
     */
    protected $apiKey;

    /**
     * Gateway to custom storage
     *
     * @var Gateway
     */
    protected $gateway;

    /**
     * Product toShop
     *
     * @var ProductToShop
     */
    protected $toShop;

    /**
     * Product fromShop
     *
     * @var ProductFromShop
     */
    protected $fromShop;

    /**
     * Error handler
     *
     * @var ErrorHandler
     */
    protected $errorHandler;

    /**
     * Service registry
     *
     * @var Rpc\ServiceRegistry
     */
    protected $registry;

    /**
     * Call marshaller
     *
     * @var Rpc\Marshaller\CallMarshaller
     */
    protected $marshaller;

    /**
     * Call unmarshaller
     *
     * @var Rpc\Marshaller\CallUnmarshaller
     */
    protected $unmarshaller;

    /**
     * Verificator dispatcher
     *
     * @var Struct\VerificatorDispatcher
     */
    protected $verificator;

    /**
     * Shopping service
     *
     * @var Service\Shopping
     */
    protected $shoppingService;

    /**
     * Verification service
     *
     * @var Service\Verification
     */
    protected $verificationService;

    /**
     * Sync service
     *
     * @var Service\Syncer
     */
    protected $syncService;

    /**
     * Metric service
     *
     * @var Service\Metric
     */
    protected $metricService;

    /**
     * Product hasher
     *
     * @var ProductHasher
     */
    protected $productHasher;

    /**
     * Revision fromShop
     *
     * @var RevisionProvider
     */
    protected $revisionFromShop;

    /**
     * SocialNetwork service
     *
     * @var Service\SocialNetwork
     */
    protected $socialNetwork;

    /**
     * Logger
     *
     * @var Logger
     */
    protected $logger;

    /**
     * @var string
     */
    protected $socialNetworkHost = 'https://sn.connect.shopware.com';

    /**
     * @var string
     */
    protected $transactionHost = 'https://transaction.connect.shopware.com';

    /**
     * @var ChangeVisitor\Message
     */
    protected $changeVisitor;

    /**
     * @var HttpClient\RequestSigner
     */
    protected $requestSigner;

    /**
     * @var string
     */
    protected $pluginSoftwareVersion;

    /**
     * Payment status service
     *
     * @var Service\PaymentStatus
     */
    protected $paymentStatusService;

    /**
     * @var Service\Export
     */
    protected $exportService;

    /**
     * @param \Shopware\Connect\Gateway $gateway
     * @param \Shopware\Connect\ProductToShop $toShop
     * @param \Shopware\Connect\ProductFromShop $fromShop
     * @param string $apiKey
     */
    public function __construct(
        Gateway $gateway,
        ProductToShop $toShop,
        ProductFromShop $fromShop,
        ErrorHandler $errorHandler,
        $apiKey,
        HttpClient\RequestSigner $requestSigner = null,
        $pluginSoftwareVersion = null
    ) {
        $this->gateway = $gateway;
        $this->toShop = $toShop;
        $this->fromShop = $fromShop;
        $this->errorHandler = $errorHandler;
        $this->apiKey = $apiKey;
        $this->requestSigner = $requestSigner;

        if ($host = getenv('_SOCIALNETWORK_HOST')) {
            $this->socialNetworkHost = "http://{$host}";
        }
        if ($host = getenv('_TRANSACTION_HOST')) {
            $this->transactionHost = "http://{$host}";
        }

        $this->apiKey = $apiKey;
        $this->pluginSoftwareVersion = $pluginSoftwareVersion;
    }

    /**
     * Get from shop gateway
     *
     * @return ProductFromShop
     */
    public function getFromShop()
    {
        return $this->fromShop;
    }

    /**
     * Get to shop gateway
     *
     * @return ProductToShop
     */
    public function getToShop()
    {
        return $this->toShop;
    }

    /**
     * Get gateway
     *
     * Access to the gateway implementation
     *
     * @return Gateway
     */
    public function getGateway()
    {
        return $this->gateway;
    }

    /**
     * Get service registry
     *
     * Direct access to this class is provided for testing and verification.
     * Use this to issue a call without the need to craft the corresponding
     * XML.
     *
     * @return Rpc\ServiceRegistry
     */
    public function getServiceRegistry()
    {
        if ($this->registry === null) {
            $this->registry = new ServiceRegistry\Metric(
                new Rpc\ServiceRegistry(
                    new ServiceRegistry\RpcErrorWrapper(
                        $this->errorHandler,
                        new Rpc\ErrorHandler\XmlErrorHandler()
                    )
                ),
                $this->pluginSoftwareVersion
            );

            $this->registry->registerMetric(
                'products',
                'fromShop',
                $this->getMetricService()
            );

            $this->registry->registerService(
                'configuration',
                array('lastRevision', 'replicate'),
                new Service\Configuration(
                    $this->gateway
                )
            );

            $this->registry->registerService(
                'products',
                array('fromShop', 'getChanges', 'peakFromShop', 'peakProducts', 'toShop', 'replicate', 'getLastRevision', 'lastRevision'),
                new Service\ProductService(
                    $this->gateway,
                    $this->gateway,
                    $this->gateway,
                    $this->toShop,
                    $this->fromShop,
                    $this->getExportService()
                )
            );

            $this->registry->registerService(
                'transaction',
                array('checkProducts', 'reserveProducts', 'buy', 'confirm'),
                new Service\Transaction(
                    $this->fromShop,
                    $this->gateway,
                    $this->getLogger(),
                    $this->gateway,
                    $this->getVerificator(),
                    $this->getSocialNetworkService(),
                    $this->apiKey
                )
            );

            $this->registry->registerService(
                'productPayments',
                array('lastRevision', 'getChanges'),
                $this->getPaymentStatusService()
            );

            $this->registry->registerService(
                'product_payments_from_shop',
                array('lastRevision', 'replicate'),
                $this->getPaymentStatusService()
            );
        }

        return $this->registry;
    }

    /**
     * Get verificator
     *
     * Direct access to this class is provided for testing and verification.
     * Use this class to verify the structs you pass to the Shopware Connect SDK. Connect
     * will do this itself, but it might be useful to also do this yourself.
     *
     * @return \Shopware\Connect\Struct\VerificatorDispatcher
     */
    public function getVerificator()
    {
        if ($this->verificator === null) {
            $this->verificator = new Struct\VerificatorDispatcher(
                array(
                    'Shopware\\Connect\\Struct\\Order' =>
                        new Struct\Verificator\Order(),
                    'Shopware\\Connect\\Struct\\OrderItem' =>
                        new Struct\Verificator\OrderItem(),
                    'Shopware\\Connect\\Struct\\Product' =>
                        new Struct\Verificator\Product(
                            new ShippingRuleParser\Google(),
                            $this->getGateway()->getConfig(SDK::CONFIG_PRICE_TYPE)
                        ),
                    'Shopware\\Connect\\Struct\\Translation' =>
                        new Struct\Verificator\Translation(),
                    'Shopware\\Connect\\Struct\\Change\\FromShop\\Insert' =>
                        new Struct\Verificator\Change\InsertOrUpdate(),
                    'Shopware\\Connect\\Struct\\Change\\FromShop\\Update' =>
                        new Struct\Verificator\Change\InsertOrUpdate(),
                    'Shopware\\Connect\\Struct\\Change\\FromShop\\Delete' =>
                        new Struct\Verificator\Change(),
                    'Shopware\\Connect\\Struct\\Change\\FromShop\\Availability' =>
                        new Struct\Verificator\Change\Availability(),
                    'Shopware\\Connect\\Struct\\Change\\FromShop\\StreamAssignment' =>
                        new Struct\Verificator\Change\StreamAssignment(),
                    'Shopware\\Connect\\Struct\\Change\\FromShop\\MakeMainVariant' =>
                        new Struct\Verificator\Change\MakeMainVariant(),
                    'Shopware\\Connect\\Struct\\Change\\ToShop\\InsertOrUpdate' =>
                        new Struct\Verificator\Change\InsertOrUpdate(),
                    'Shopware\\Connect\\Struct\\Change\\ToShop\\Delete' =>
                        new Struct\Verificator\Change\Delete(),
                    'Shopware\\Connect\\Struct\\Change\\ToShop\\Availability' =>
                        new Struct\Verificator\Change\Availability(),
                    'Shopware\\Connect\\Struct\\Change\\InterShop\\Update' =>
                        new Struct\Verificator\Change\InterShopUpdate(),
                    'Shopware\\Connect\\Struct\\Change\\InterShop\\Delete' =>
                        new Struct\Verificator\Change\InterShopDelete(),
                    'Shopware\\Connect\\Struct\\Change\\InterShop\\Unavailable' =>
                        new Struct\Verificator\Change\InterShopUnavailable(),
                    'Shopware\\Connect\\Struct\\ShopConfiguration' =>
                        new Struct\Verificator\ShopConfiguration(),
                    'Shopware\\Connect\\Struct\\Reservation' =>
                        new Struct\Verificator\Reservation(),
                    'Shopware\\Connect\\Struct\\Message' =>
                        new Struct\Verificator\Message(),
                    'Shopware\\Connect\\Struct\\Address' =>
                        new Struct\Verificator\Address(),
                    'Shopware\\Connect\\Struct\\ProductList' =>
                        new Struct\Verificator\ProductList(),
                    'Shopware\\Connect\\Struct\\Tracking' =>
                        new Struct\Verificator\Tracking(),
                    'Shopware\\Connect\\Struct\\OrderStatus' =>
                        new Struct\Verificator\OrderStatus(),
                    'Shopware\\Connect\\Struct\\PaymentStatus' =>
                        new Struct\Verificator\PaymentStatus(),
                    'Shopware\\Connect\\Struct\\Shipping' =>
                        new Struct\Verificator\Shipping(),
                    'Shopware\\Connect\\Struct\\ShippingRules' =>
                        new Struct\Verificator\ShippingRules(),
                )
            );
        }

        return $this->verificator;
    }

    /**
     * @return Rpc\Marshaller\CallUnmarshaller
     */
    public function getUnmarshaller()
    {
        if ($this->unmarshaller === null) {
            $this->unmarshaller = new Rpc\Marshaller\CallUnmarshaller\XmlCallUnmarshaller(
                new Rpc\Marshaller\Converter\ErrorToExceptionConverter()
            );
        }

        return $this->unmarshaller;
    }

    /**
     * @return Rpc\Marshaller\CallMarshaller
     */
    public function getMarshaller()
    {
        if ($this->marshaller === null) {
            $this->marshaller = new Rpc\Marshaller\CallMarshaller\XmlCallMarshaller(
                new \Shopware\Connect\XmlHelper(),
                new Rpc\Marshaller\Converter\ChainingConverter(array(
                    new Rpc\Marshaller\Converter\ExceptionToErrorConverter(),
                    new Rpc\Marshaller\Converter\LegacyOrderConverter(),
                ))
            );
        }

        return $this->marshaller;
    }

    /**
     * @return Service\Shopping
     */
    public function getShoppingService()
    {
        if ($this->shoppingService === null) {
            $this->shoppingService = new Service\Shopping(
                new ShopFactory\Http(
                    $this,
                    $this->gateway
                ),
                $this->getChangeVisitor(),
                $this->toShop,
                $this->getLogger(),
                $this->errorHandler,
                $this->gateway
            );
        }

        return $this->shoppingService;
    }

    /**
     * @return Service\Verification
     */
    public function getVerificationService()
    {
        if ($this->verificationService === null) {
            $this->verificationService = new Service\Verification(
                $this->getHttpClient($this->socialNetworkHost),
                $this->gateway
            );
        }

        return $this->verificationService;
    }

    /**
     * @return Service\Syncer
     */
    public function getSyncService()
    {
        if ($this->syncService === null) {
            $this->syncService = new Service\Syncer(
                $this->gateway,
                $this->gateway,
                $this->fromShop,
                $this->getRevisionProvider(),
                $this->getProductHasher()
            );
        }

        return $this->syncService;
    }

    /**
     * @return Service\Metric
     */
    public function getMetricService()
    {
        if ($this->metricService === null) {
            $this->metricService = new Service\Metric(
                $this->gateway
            );
        }

        return $this->metricService;
    }

    /**
     * @return ProductHasher
     */
    public function getProductHasher()
    {
        if ($this->productHasher === null) {
            $this->productHasher = new ProductHasher\Simple();
        }

        return $this->productHasher;
    }

    /**
     * @return RevisionProvider
     */
    public function getRevisionProvider()
    {
        if ($this->revisionFromShop === null) {
            $this->revisionFromShop = new RevisionProvider\Time();
        }

        return $this->revisionFromShop;
    }

    /**
     * @return ChangeVisitor
     */
    public function getChangeVisitor()
    {
        if ($this->changeVisitor === null) {
            $this->changeVisitor = new ChangeVisitor\Message(
                $this->getVerificator()
            );
        }

        return $this->changeVisitor;
    }

    /**
     * @return Logger
     */
    public function getLogger()
    {
        if ($this->logger === null) {
            $this->logger = new Logger\Http(
                $this->getHttpClient($this->transactionHost),
                $this->apiKey
            );
        }

        return $this->logger;
    }

    /**
     * @param string $server
     *
     * @return \Shopware\Connect\HttpClient
     */
    public function getHttpClient($server)
    {
        $version = strpos(SDK::VERSION, '$') === 0 ? 'dev' : SDK::VERSION;

        $headers = array(
            'X-Connect-SDK-Version: ' . $version,
            'Accept: applications/x-shopware-connect-json-' . $version,
        );

        $client = new HttpClient\Stream($server);
        $client->addDefaultHeaders($headers);

        return $client;
    }

    /**
     * @return HttpClient\RequestSigner
     */
    public function getRequestSigner()
    {
        if ($this->requestSigner === null) {
            $this->requestSigner = new HttpClient\SharedKeyRequestSigner(
                $this->getGateway(),
                new Service\Clock(),
                $this->apiKey
            );
        }

        return $this->requestSigner;
    }

    /**
     * @return Service\SocialNetwork
     */
    public function getSocialNetworkService()
    {
        if ($this->socialNetwork === null) {
            $this->socialNetwork = new Service\SocialNetwork(
                $this->getHttpClient($this->socialNetworkHost),
                $this->getVerificator(),
                $this->gateway->getShopId(),
                $this->apiKey
            );
        }

        return $this->socialNetwork;
    }

    /**
     * @return Service\PaymentStatus
     */
    public function getPaymentStatusService()
    {
        if ($this->paymentStatusService === null) {
            $this->paymentStatusService = new Service\PaymentStatus(
                $this->gateway,
                $this->fromShop,
                $this->getGateway()
            );
        }

        return $this->paymentStatusService;
    }

    /**
     * @return Service\Export
     */
    public function getExportService()
    {
        if ($this->exportService === null) {
            $this->exportService = new Service\Export(
                $this->getFromShop(),
                $this->getVerificator(),
                $this->getGateway(),
                $this->getProductHasher(),
                $this->getRevisionProvider()
            );
        }

        return $this->exportService;
    }
}
