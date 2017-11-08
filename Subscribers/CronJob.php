<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Subscribers;

use Enlight\Event\SubscriberInterface;
use Shopware\Connect\SDK;
use Shopware\CustomModels\Connect\Attribute;
use Shopware\Models\ProductStream\ProductStream;
use ShopwarePlugins\Connect\Components\Config;
use ShopwarePlugins\Connect\Components\ErrorHandler;
use ShopwarePlugins\Connect\Components\Helper;
use ShopwarePlugins\Connect\Components\ImageImport;
use ShopwarePlugins\Connect\Components\Logger;
use ShopwarePlugins\Connect\Components\ConnectExport;
use ShopwarePlugins\Connect\Components\ProductStream\ProductSearch;
use ShopwarePlugins\Connect\Components\ProductStream\ProductStreamService;
use Shopware\Components\DependencyInjection\Container;

/**
 * Cronjob callback
 *
 * Class CronJob
 * @package ShopwarePlugins\Connect\Subscribers
 */
class CronJob implements SubscriberInterface
{
    /**
     * @var \ShopwarePlugins\Connect\Components\Config
     */
    private $configComponent;

    /**
     * @var SDK
     */
    protected $sdk;

    /**
     * @var ProductStreamService
     */
    protected $streamService;

    /**
     * @var ConnectExport
     */
    protected $connectExport;

    /**
     * @var Helper
     */
    private $helper;

    /**
     * @var ProductSearch
     */
    private $productSearch;

    /**
     * @var Container
     */
    private $container;

    /**
     * @var ProductStreamService
     */
    private $productStreamService;

    /**
     * @param SDK $sdk
     * @param ConnectExport $connectExport
     * @param Config $configComponent
     * @param Helper $helper
     * @param Container $container
     * @param ProductStreamService $productStreamService
     */
    public function __construct(
        SDK $sdk,
        ConnectExport $connectExport,
        Config $configComponent,
        Helper $helper,
        Container $container,
        ProductStreamService $productStreamService
    ) {
        $this->connectExport = $connectExport;
        $this->sdk = $sdk;
        $this->configComponent = $configComponent;
        $this->helper = $helper;
        $this->container = $container;
        $this->productStreamService = $productStreamService;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            'Shopware_CronJob_ShopwareConnectImportImages' => 'importImages',
            'Shopware_CronJob_ShopwareConnectUpdateProducts' => 'updateProducts',
            'Shopware_CronJob_ConnectExportDynamicStreams' => 'exportDynamicStreams',
        ];
    }

    /**
     * @return ImageImport
     */
    public function getImageImport()
    {
        // do not use thumbnail_manager as a dependency!!!
        // MediaService::__construct uses Shop entity
        // this also could break the session in backend when it's used in subscriber
        return new ImageImport(
            Shopware()->Models(),
            $this->helper,
            $this->container->get('thumbnail_manager'),
            new Logger(Shopware()->Db())
        );
    }

    /**
     * Import images of new products
     *
     * @param \Shopware_Components_Cron_CronJob $job
     * @return bool
     */
    public function importImages(\Shopware_Components_Cron_CronJob $job)
    {
        $limit = $this->configComponent->getConfig('articleImagesLimitImport', 10);
        $this->getImageImport()->import($limit);

        return true;
    }

    /**
     * Collect all own products and send them
     * to Connect system.
     *
     * Used to update products with many variants.
     *
     * @param \Shopware_Components_Cron_CronJob $job
     * @return bool
     */
    public function updateProducts(\Shopware_Components_Cron_CronJob $job)
    {
        $sourceIds = Shopware()->Db()->fetchCol(
            'SELECT source_id FROM s_plugin_connect_items WHERE shop_id IS NULL AND cron_update = 1 LIMIT 100'
        );

        if (empty($sourceIds)) {
            return true;
        }

        $this->connectExport->export($sourceIds);

        $quotedSourceIds = Shopware()->Db()->quote($sourceIds);
        Shopware()->Db()->query("
            UPDATE s_plugin_connect_items
            SET cron_update = false
            WHERE source_id IN ($quotedSourceIds)"
        )->execute();

        return true;
    }

    /**
     * @param \Shopware_Components_Cron_CronJob $job
     */
    public function exportDynamicStreams(\Shopware_Components_Cron_CronJob $job)
    {
        $streams = $this->productStreamService->getAllExportedStreams(ProductStreamService::DYNAMIC_STREAM);

        /** @var ProductStream $stream */
        foreach ($streams as $stream) {
            $streamId = $stream->getId();
            $productSearchResult = $this->getProductSearch()->getProductFromConditionStream($stream);
            $orderNumbers = array_keys($productSearchResult->getProducts());

            //no products found
            if (!$orderNumbers) {
                //removes all products from this stream
                $this->productStreamService->markProductsToBeRemovedFromStream($streamId);
            } else {
                $articleIds = $this->helper->getArticleIdsByNumber($orderNumbers);

                $this->productStreamService->markProductsToBeRemovedFromStream($streamId);
                $this->productStreamService->createStreamRelation($streamId, $articleIds);
            }

            try {
                $streamsAssignments = $this->productStreamService->prepareStreamsAssignments($streamId, false);

                //article ids must be taken from streamsAssignments
                $exportArticleIds = $streamsAssignments->getArticleIds();

                $removeArticleIds = $streamsAssignments->getArticleIdsWithoutStreams();

                if (!empty($removeArticleIds)) {
                    $this->removeArticlesFromStream($removeArticleIds);

                    //filter the $exportArticleIds
                    $exportArticleIds = array_diff($exportArticleIds, $removeArticleIds);
                }

                $sourceIds = $this->helper->getArticleSourceIds($exportArticleIds);

                $errorMessages = $this->connectExport->export($sourceIds, $streamsAssignments);
                $this->productStreamService->changeStatus($streamId, ProductStreamService::STATUS_EXPORT);
            } catch (\RuntimeException $e) {
                $this->productStreamService->changeStatus($streamId, ProductStreamService::STATUS_ERROR, $e->getMessage());
                continue;
            }

            if ($errorMessages) {
                $errorMessagesText = '';
                $displayedErrorTypes = [
                    ErrorHandler::TYPE_DEFAULT_ERROR,
                    ErrorHandler::TYPE_PRICE_ERROR
                ];

                foreach ($displayedErrorTypes as $displayedErrorType) {
                    $errorMessagesText .= implode('\n', $errorMessages[$displayedErrorType]);
                }

                $this->productStreamService->changeStatus($streamId, ProductStreamService::STATUS_ERROR, $errorMessagesText);
            }
        }
    }

    /**
     * If article is not taking part of any shopware stream it will be removed
     * @param array $articleIds
     */
    private function removeArticlesFromStream(array $articleIds)
    {
        $sourceIds = $this->helper->getArticleSourceIds($articleIds);
        $items = $this->connectExport->fetchConnectItems($sourceIds, false);

        foreach ($items as $item) {
            $this->sdk->recordDelete($item['sourceId']);
        }

        $this->productStreamService->removeMarkedStreamRelations();
        $this->connectExport->updateConnectItemsStatus($sourceIds, Attribute::STATUS_DELETE);
    }

    /**
     * @return ProductSearch
     */
    private function getProductSearch()
    {
        if (!$this->productSearch) {
            // HACK
            // do not use as a dependency!!!
            // this class uses Shopware product search which depends on shop context
            // so if it's used as dependency of subscriber, plugin returns error on deactivate
            // see CON-4922
            $this->productSearch = $this->container->get('swagconnect.product_search');
        }

        return $this->productSearch;
    }
}
