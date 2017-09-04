<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Subscribers;

use Shopware\Connect\SDK;
use Shopware\CustomModels\Connect\Attribute;
use Shopware\Models\ProductStream\ProductStream;
use ShopwarePlugins\Connect\Components\ConfigFactory;
use ShopwarePlugins\Connect\Components\ErrorHandler;
use ShopwarePlugins\Connect\Components\ImageImport;
use ShopwarePlugins\Connect\Components\Logger;
use ShopwarePlugins\Connect\Components\ConnectExport;
use ShopwarePlugins\Connect\Components\ProductStream\ProductStreamService;

/**
 * Cronjob callback
 *
 * Class CronJob
 * @package ShopwarePlugins\Connect\Subscribers
 */
class CronJob extends BaseSubscriber
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
     * @param SDK $sdk
     * @param ConnectExport $connectExport
     */
    public function __construct(
        SDK $sdk,
        ConnectExport $connectExport
    ) {
        parent::__construct();
        $this->connectExport = $connectExport;
        $this->sdk = $sdk;
    }

    public function getSubscribedEvents()
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
        return new ImageImport(
            Shopware()->Models(),
            $this->getHelper(),
            Shopware()->Container()->get('thumbnail_manager'),
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
        $limit = $this->getConfigComponent()->getConfig('articleImagesLimitImport', 10);
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
        /** @var ProductStreamService $streamService */
        $streamService = $this->getStreamService();
        $streams = $streamService->getAllExportedStreams(ProductStreamService::DYNAMIC_STREAM);

        /** @var ProductStream $stream */
        foreach ($streams as $stream) {
            $streamId = $stream->getId();
            $productSearchResult = $streamService->getProductFromConditionStream($stream);
            $orderNumbers = array_keys($productSearchResult->getProducts());

            //no products found
            if (!$orderNumbers) {
                //removes all products from this stream
                $streamService->markProductsToBeRemovedFromStream($streamId);
            } else {
                $articleIds = $this->getHelper()->getArticleIdsByNumber($orderNumbers);

                $streamService->markProductsToBeRemovedFromStream($streamId);
                $streamService->createStreamRelation($streamId, $articleIds);
            }

            try {
                $streamsAssignments = $streamService->prepareStreamsAssignments($streamId, false);

                //article ids must be taken from streamsAssignments
                $exportArticleIds = $streamsAssignments->getArticleIds();

                $removeArticleIds = $streamsAssignments->getArticleIdsWithoutStreams();

                if (!empty($removeArticleIds)) {
                    $this->removeArticlesFromStream($removeArticleIds);

                    //filter the $exportArticleIds
                    $exportArticleIds = array_diff($exportArticleIds, $removeArticleIds);
                }

                $sourceIds = $this->getHelper()->getArticleSourceIds($exportArticleIds);

                $errorMessages = $this->connectExport->export($sourceIds, $streamsAssignments);
                $streamService->changeStatus($streamId, ProductStreamService::STATUS_EXPORT);
            } catch (\RuntimeException $e) {
                $streamService->changeStatus($streamId, ProductStreamService::STATUS_ERROR);
                $streamService->log($streamId, $e->getMessage());
                continue;
            }

            if ($errorMessages) {
                $streamService->changeStatus($streamId, ProductStreamService::STATUS_ERROR);

                $errorMessagesText = '';
                $displayedErrorTypes = [
                    ErrorHandler::TYPE_DEFAULT_ERROR,
                    ErrorHandler::TYPE_PRICE_ERROR
                ];

                foreach ($displayedErrorTypes as $displayedErrorType) {
                    $errorMessagesText .= implode('\n', $errorMessages[$displayedErrorType]);
                }

                $streamService->log($streamId, $errorMessagesText);
            }
        }
    }

    /**
     * If article is not taking part of any shopware stream it will be removed
     * @param array $articleIds
     */
    private function removeArticlesFromStream(array $articleIds)
    {
        $sourceIds = $this->getHelper()->getArticleSourceIds($articleIds);
        $items = $this->connectExport->fetchConnectItems($sourceIds, false);

        foreach ($items as $item) {
            $this->getSDK()->recordDelete($item['sourceId']);
        }

        $this->getStreamService()->removeMarkedStreamRelations();
        $this->connectExport->updateConnectItemsStatus($sourceIds, Attribute::STATUS_DELETE);
    }

    /**
     * @return ProductStreamService $streamService
     */
    private function getStreamService()
    {
        if (!$this->streamService) {
            $this->streamService = $this->Application()->Container()->get('swagconnect.product_stream_service');
        }

        return $this->streamService;
    }

    private function getConfigComponent()
    {
        if (!$this->configComponent) {
            $this->configComponent = ConfigFactory::getConfigInstance();
        }

        return $this->configComponent;
    }
}
