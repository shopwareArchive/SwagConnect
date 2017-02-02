<?php

namespace ShopwarePlugins\Connect\Subscribers;
use Shopware\Models\ProductStream\ProductStream;
use ShopwarePlugins\Connect\Components\Config;
use ShopwarePlugins\Connect\Components\ErrorHandler;
use ShopwarePlugins\Connect\Components\ImageImport;
use ShopwarePlugins\Connect\Components\Logger;
use ShopwarePlugins\Connect\Components\ConnectExport;
use ShopwarePlugins\Connect\Components\ProductStream\ProductStreamService;
use ShopwarePlugins\Connect\Components\Validator\ProductAttributesValidator\ProductsAttributesValidator;

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

    /** @var ConnectExport */
    protected $connectExport;

    /**
     * CronJob constructor.
     * @param ConnectExport $connectExport
     */
    public function __construct(
        ConnectExport $connectExport
    ) {
        parent::__construct();
        $this->connectExport = $connectExport;
    }

    public function getSubscribedEvents()
    {
        //todo@sb: fix cronjobs via bin/console
        return array(
            'Shopware_CronJob_ShopwareConnectImportImages' => 'importImages',
            'Shopware_CronJob_ShopwareConnectUpdateProducts' => 'updateProducts',
            'Shopware_CronJob_ConnectExportDynamicStreams' => 'exportDynamicStreams',
        );
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

    public function exportDynamicStreams(\Shopware_Components_Cron_CronJob $job)
    {
        /** @var ProductStreamService $streamService */
        $streamService = $this->Application()->Container()->get('swagconnect.product_stream_service');
        $streams = $streamService->getAllExportedStreams(ProductStreamService::DYNAMIC_STREAM);

        /** @var ProductStream $stream */
        foreach ($streams as $stream) {
            $productSearchResult = $streamService->getProductFromConditionStream($stream);
            $orderNumbers = array_keys($productSearchResult->getProducts());

            //no products found
            if (!$orderNumbers) {
                continue;
            }

            $streamId = $stream->getId();
            $articleIds = $this->getHelper()->getArticleIdsByNumber($orderNumbers);
            $streamService->createStreamRelation($stream->getId(), $articleIds);

            $streamsAssignments = $streamService->prepareStreamsAssignments($streamId);

            if (!$streamsAssignments) {
                return;
            }

            $sourceIds = $this->getHelper()->getArticleSourceIds($articleIds);

            try {
                $errorMessages = $this->connectExport->export($sourceIds, $streamsAssignments);
                $streamService->changeStatus($streamId, ProductStreamService::STATUS_EXPORT);
            } catch (\RuntimeException $e) {
                $streamService->changeStatus($streamId, ProductStreamService::STATUS_ERROR);
                $streamService->log($streamId, $e->getMessage());
                continue;
            }

            if ($errorMessages) {
                $streamService->changeStatus($streamId, ProductStreamService::STATUS_ERROR);

                $errorMessagesText = "";
                $displayedErrorTypes = array(
                    ErrorHandler::TYPE_DEFAULT_ERROR,
                    ErrorHandler::TYPE_PRICE_ERROR
                );

                foreach ($displayedErrorTypes as $displayedErrorType) {
                    $errorMessagesText .= implode('\n', $errorMessages[$displayedErrorType]);
                }

                $streamService->log($streamId, $errorMessagesText);
            }
        }
    }

    private function getConfigComponent()
    {
        if (!$this->configComponent) {
            $this->configComponent = new Config(Shopware()->Models());
        }

        return $this->configComponent;
    }
}