<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Bepado\SDK;

use Bepado\SDK\Rpc;
use Bepado\SDK\Struct\RpcCall;

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
     * Shipping costs service
     *
     * @var Service\ShippingCosts
     */
    protected $shippingCostsService;

    /**
     * Verification service
     *
     * @var Service\Verification
     */
    protected $verificationService;

    /**
     * Search service
     *
     * @var Service\Search
     */
    protected $searchService;

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
    protected $socialNetworkHost = 'https://sn.bepado.de';

    /**
     * @var string
     */
    protected $transactionHost = 'https://transaction.bepado.de';

    /**
     * @var string
     */
    protected $searchHost = 'https://search.bepado.de';

    /**
     * @var ChangeVisitor\Message
     */
    protected $changeVisitor;

    /**
     * @var HttpClient\RequestSigner
     */
    protected $requestSigner;

    /**
     * @var ShippingCostCalculator
     */
    protected $shippingCostCalculator;

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
     * @var ProductPayments
     */
    protected $productPayments;

    /**
     * @param \Bepado\SDK\Gateway $gateway
     * @param \Bepado\SDK\ProductToShop $toShop
     * @param \Bepado\SDK\ProductFromShop $fromShop
     * @param string $apiKey
     */
    public function __construct(
        Gateway $gateway,
        ProductToShop $toShop,
        ProductFromShop $fromShop,
        ErrorHandler $errorHandler,
        $apiKey,
        HttpClient\RequestSigner $requestSigner = null,
        $pluginSoftwareVersion = null,
        ProductPayments $productPayments = null
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
        if ($host = getenv('_SEARCH_HOST')) {
            $this->searchHost = "http://{$host}";
        }

        $this->apiKey = $apiKey;
        $this->pluginSoftwareVersion = $pluginSoftwareVersion;
        $this->productPayments = $productPayments ?: new NoopProductPayments();
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
                array('update', 'lastRevision', 'replicate'),
                new Service\Configuration(
                    $this->gateway
                )
            );

            $this->registry->registerService(
                'categories',
                array('lastRevision', 'replicate'),
                new Service\Categories(
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
                    $this->fromShop
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
                    $this->getShippingCostsService(),
                    $this->getVerificator()
                )
            );

            $this->registry->registerService(
                'shippingCosts',
                array('lastRevision', 'replicate'),
                $this->getShippingCostsService()
            );

            $this->registry->registerService(
                'productPayments',
                array('lastRevision', 'updatePaymentStatus'),
                $this->getPaymentStatusService()
            );
        }

        return $this->registry;
    }

    /**
     * Get verificator
     *
     * Direct access to this class is provided for testing and verification.
     * Use this class to verify the structs you pass to the Bepado SDK. Bepado
     * will do this itself, but it might be useful to also do this yourself.
     *
     * @return \Bepado\SDK\Struct\VerificatorDispatcher
     */
    public function getVerificator()
    {
        if ($this->verificator === null) {
            $this->verificator = new Struct\VerificatorDispatcher(
                array(
                    'Bepado\\SDK\\Struct\\Order' =>
                        new Struct\Verificator\Order(),
                    'Bepado\\SDK\\Struct\\OrderItem' =>
                        new Struct\Verificator\OrderItem(),
                    'Bepado\\SDK\\Struct\\Product' =>
                        new Struct\Verificator\Product(
                            new ShippingRuleParser\Google()
                        ),
                    'Bepado\\SDK\\Struct\\Change\\FromShop\\Insert' =>
                        new Struct\Verificator\Change\InsertOrUpdate(),
                    'Bepado\\SDK\\Struct\\Change\\FromShop\\Update' =>
                        new Struct\Verificator\Change\InsertOrUpdate(),
                    'Bepado\\SDK\\Struct\\Change\\FromShop\\Delete' =>
                        new Struct\Verificator\Change(),
                    'Bepado\\SDK\\Struct\\Change\\ToShop\\InsertOrUpdate' =>
                        new Struct\Verificator\Change\InsertOrUpdate(),
                    'Bepado\\SDK\\Struct\\Change\\ToShop\\Delete' =>
                        new Struct\Verificator\Change\Delete(),
                    'Bepado\\SDK\\Struct\\Change\\InterShop\\Update' =>
                        new Struct\Verificator\Change\InterShopUpdate(),
                    'Bepado\\SDK\\Struct\\Change\\InterShop\\Delete' =>
                        new Struct\Verificator\Change\InterShopDelete(),
                    'Bepado\\SDK\\Struct\\ShopConfiguration' =>
                        new Struct\Verificator\ShopConfiguration(),
                    'Bepado\\SDK\\Struct\\Reservation' =>
                        new Struct\Verificator\Reservation(),
                    'Bepado\\SDK\\Struct\\Message' =>
                        new Struct\Verificator\Message(),
                    'Bepado\\SDK\\Struct\\Address' =>
                        new Struct\Verificator\Address(),
                    'Bepado\\SDK\\Struct\\ProductList' =>
                        new Struct\Verificator\ProductList(),
                    'Bepado\\SDK\\Struct\\Tracking' =>
                        new Struct\Verificator\Tracking(),
                    'Bepado\\SDK\\Struct\\OrderStatus' =>
                        new Struct\Verificator\OrderStatus(),
                    'Bepado\\SDK\\Struct\\Shipping' =>
                        new Struct\Verificator\Shipping(),
                    'Bepado\\SDK\\Struct\\ShippingRules' =>
                        new Struct\Verificator\ShippingRules(),
                    'Bepado\\SDK\\ShippingCosts\\Rule\\Product' =>
                        new Struct\Verificator\ProductRule(),
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
                new \Bepado\SDK\XmlHelper(),
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
                $this->getShippingCostsService(),
                $this->gateway
            );
        }

        return $this->shoppingService;
    }

    /**
     * @return Service\ShippingCosts
     */
    public function getShippingCostsService()
    {
        if ($this->shippingCostsService === null) {
            $this->shippingCostsService = new Service\ShippingCosts(
                $this->gateway,
                $this->getShippingCostCalculator()
            );
        }

        return $this->shippingCostsService;
    }

    public function getShippingCostCalculator()
    {
        if ($this->shippingCostCalculator === null) {
            if ($this->gateway->isFeatureEnabled('shipping_rules')) {
                $this->shippingCostCalculator = new ShippingCostCalculator\ProductCalculator(
                    new ShippingCostCalculator\RuleCalculator(),
                    new ShippingRuleParser\Validator(
                        new ShippingRuleParser\Google(),
                        $this->getVerificator()
                    )
                );
            } else {
                $this->shippingCostCalculator = new ShippingCostCalculator\GlobalConfigCalculator(
                    $this->gateway
                );
            }
        }

        return $this->shippingCostCalculator;
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
     * @return Service\Search
     */
    public function getSearchService()
    {
        if ($this->searchService === null) {
            $this->searchService = new Service\Search(
                $this->getHttpClient($this->searchHost),
                $this->apiKey,
                $this->gateway->getShopId()
            );
        }

        return $this->searchService;
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
     * @return \Bepado\SDK\HttpClient
     */
    public function getHttpClient($server)
    {
        $version = strpos(SDK::VERSION, '$') === 0 ? 'dev' : SDK::VERSION;

        $headers = array(
            'X-Bepado-SDK-Version: ' . $version,
            'Accept: applications/x-bepado-json-' . $version,
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
                $this->productPayments
            );
        }

        return $this->paymentStatusService;
    }
}
