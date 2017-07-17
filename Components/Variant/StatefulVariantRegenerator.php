<?php

namespace ShopwarePlugins\Connect\Components\Variant;

use Shopware\CustomModels\Connect\Attribute;
use ShopwarePlugins\Connect\Components\Config;
use ShopwarePlugins\Connect\Components\ProductStream\ProductStreamsAssignments;
use ShopwarePlugins\Connect\Components\VariantRegenerator;
use Shopware\Connect\SDK;
use ShopwarePlugins\Connect\Components\ConnectExport;
use ShopwarePlugins\Connect\Components\ProductStream\ProductStreamService;

class StatefulVariantRegenerator implements VariantRegenerator
{
    private static $initialSourceIds = [];

    private static $currentSourceIds = [];

    private $sdk;

    private $connectExport;

    /**
     * @var ProductStreamService
     */
    private $productStreamService;

    /**
     * @var string
     */
    private $autoUpdateProducts;

    /**
     * StatefulVariantRegenerator constructor.
     * @param SDK $sdk
     * @param ConnectExport $connectExport
     * @param ProductStreamService $productStreamService
     * @param $autoUpdateProducts
     */
    public function __construct(
        SDK $sdk,
        ConnectExport $connectExport,
        ProductStreamService $productStreamService,
        $autoUpdateProducts
    ) {
        $this->sdk = $sdk;
        $this->connectExport = $connectExport;
        $this->productStreamService = $productStreamService;
        $this->autoUpdateProducts = $autoUpdateProducts;
    }

    /**
     * @param int $articleId
     * @param array $sourceIds
     */
    public function setInitialSourceIds($articleId, array $sourceIds)
    {
        self::$initialSourceIds[$articleId] = $sourceIds;
    }

    /**
     * @param int $articleId
     * @param array $sourceIds
     */
    public function setCurrentSourceIds($articleId, array $sourceIds)
    {
        self::$currentSourceIds[$articleId] = $sourceIds;
    }

    /**
     * @param int $articleId
     */
    public function generateChanges($articleId)
    {
        if (!isset(self::$initialSourceIds[$articleId])) {
            throw new \InvalidArgumentException('Initial sourceIds are not set!');
        }

        if (!isset(self::$currentSourceIds[$articleId])) {
            throw new \InvalidArgumentException('Current sourceIds are not set!');
        }

        $sourceIdsForDelete = array_diff(
            self::$initialSourceIds[$articleId],
            self::$currentSourceIds[$articleId]
        );
        foreach ($sourceIdsForDelete as $sourceId) {
            $this->sdk->recordDelete($sourceId);
        }

        $this->connectExport->updateConnectItemsStatus($sourceIdsForDelete, Attribute::STATUS_DELETE);

        if ($this->autoUpdateProducts == Config::UPDATE_CRON_JOB) {
            $this->connectExport->markArticleForCronUpdate($articleId);
            return;
        }

        $this->connectExport->export(
            self::$currentSourceIds,
            new ProductStreamsAssignments(
                ['assignments' => $this->productStreamService->collectRelatedStreamsAssignments([$articleId])]
            )
        );

        unset(self::$initialSourceIds[$articleId]);
        unset(self::$currentSourceIds[$articleId]);
    }
}