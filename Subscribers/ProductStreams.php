<?php

namespace ShopwarePlugins\Connect\Subscribers;

use Doctrine\Common\Collections\ArrayCollection;
use ShopwarePlugins\Connect\Bundle\SearchBundleDBAL\ConditionHandler\SupplierConditionHandler;
use Shopware\CustomModels\Connect\Attribute;
use ShopwarePlugins\Connect\Components\Config;
use ShopwarePlugins\Connect\Components\ConnectExport;
use ShopwarePlugins\Connect\Components\Helper;
use ShopwarePlugins\Connect\Components\ProductStream\ProductStreamService;

class ProductStreams extends BaseSubscriber
{
    /** @var ConnectExport */
    protected $connectExport;

    /** @var Config */
    protected $config;

    /** @var  Helper */
    protected $helper;

    /**
     * ProductStreams constructor.
     * @param ConnectExport $connectExport
     * @param Config $config
     * @param Helper $helper
     */
    public function __construct(
        ConnectExport $connectExport,
        Config $config,
        Helper $helper

    ) {
        parent::__construct();
        $this->connectExport = $connectExport;
        $this->config = $config;
        $this->helper = $helper;
    }

    public function getSubscribedEvents()
    {
        return array(
            'Enlight_Controller_Action_PreDispatch_Backend_ProductStream' => 'preProductStream',
            'Enlight_Controller_Action_PostDispatch_Backend_ProductStream' => 'extendBackendProductStream',
            'Shopware_SearchBundleDBAL_Collect_Condition_Handlers' => 'registerConditionHandlers'
        );
    }

    public function getProductStreamService()
    {
        return $this->Application()->Container()->get('swagconnect.product_stream_service');
    }

    public function preProductStream(\Enlight_Event_EventArgs $args)
    {
        /** @var $subject \Enlight_Controller_Action */
        $subject = $args->getSubject();
        $request = $subject->Request();

        switch ($request->getActionName()) {
            case 'delete':
                $streamId = $request->get('id');
                /** @var ProductStreamService $productStreamService */
                $productStreamService = $this->getProductStreamService();

                if ($productStreamService->isStreamExported($streamId)) {
                    $assignments = $productStreamService->getStreamAssignments($streamId);
                    $this->removeArticlesFromStream($streamId, $assignments, $assignments->getArticleIds());
                    $this->getSDK()->recordStreamDelete($streamId);
                    $productStreamService->changeStatus($streamId, ProductStreamService::STATUS_DELETE);
                }
                break;
            case 'removeSelectedProduct':
                $streamId = $request->get('streamId');
                $articleId = $request->get('articleId');

                if (!$streamId || !$articleId) {
                    return;
                }

                /** @var ProductStreamService $productStreamService */
                $productStreamService = $this->getProductStreamService();

                if ($productStreamService->isStreamExported($streamId)) {
                    $assignments = $productStreamService->getStreamAssignments($streamId);
                    $this->removeArticlesFromStream($streamId, $assignments, [$articleId]);
                }
                break;
            default:
                break;
        }
    }

    /**
     * @event Enlight_Controller_Action_PostDispatch_Backend_Article
     * @param \Enlight_Event_EventArgs $args
     */
    public function extendBackendProductStream(\Enlight_Event_EventArgs $args)
    {
        /** @var $subject \Enlight_Controller_Action */
        $subject = $args->getSubject();
        $request = $subject->Request();

        switch ($request->getActionName()) {
            case 'index':
                $this->registerMyTemplateDir();
                $this->registerMySnippets();
                $subject->View()->extendsTemplate(
                    'backend/product_stream/connect_app.js'
                );
                break;
            case 'load':
                $this->registerMyTemplateDir();
                $this->registerMySnippets();

                $subject->View()->extendsTemplate(
                    'backend/product_stream/view/selected_list/connect_product.js'
                );
                $subject->View()->extendsTemplate(
                    'backend/product_stream/view/condition_list/connect_condition_panel.js'
                );
                break;
            case 'addSelectedProduct':
                $streamId = $request->getParam('streamId');
                $articleId = $request->getParam('articleId');

                if (!$this->getProductStreamService()->isStreamExported($streamId)) {
                    return;
                }

                $autoUpdate = $this->config->getConfig('autoUpdateProducts', Config::UPDATE_AUTO);
                if ($autoUpdate == Config::UPDATE_MANUAL) {
                    return;
                }

                $sourceIds = $this->helper->getSourceIdsFromArticleId($articleId);

                //it can timeout if products are more than a 100
                if (count($sourceIds) > ProductStreamService::PRODUCT_LIMIT) {
                    return;
                }

                $streamAssignments = $this->getProductStreamService()->prepareStreamsAssignments($streamId);

                if ($autoUpdate == Config::UPDATE_AUTO) {
                    $this->connectExport->export($sourceIds, $streamAssignments);
                } elseif ($autoUpdate == Config::UPDATE_CRON_JOB) {
                    Shopware()->Db()->update(
                        's_plugin_connect_items',
                        array('cron_update' => 1),
                        array('article_id' => $articleId)
                    );
                }
                break;
            default:
                break;
        }
    }

    /**
     * @param $streamId
     * @param $assignments
     * @param array $articleIds
     */
    private function removeArticlesFromStream($streamId, $assignments, array $articleIds)
    {
        $removedRecords = [];
        $productStreamService = $this->getProductStreamService();
        $sourceIds = $this->getHelper()->getArticleSourceIds($articleIds);
        $items = $this->connectExport->fetchConnectItems($sourceIds, false);

        foreach ($items as $item) {
            if ($productStreamService->allowToRemove($assignments, $streamId, $item['articleId'])) {
                $this->getSDK()->recordDelete($item['sourceId']);
                $removedRecords[] = $item['sourceId'];
            } else {
                //updates items with the new streams
                $streamCollection = $assignments->getStreamsByArticleId($item['articleId']);
                if (!$this->getHelper()->isMainVariant($item['sourceId']) || !$streamCollection) {
                    continue;
                }

                //removes current stream from the collection
                unset($streamCollection[$streamId]);

                $this->getSDK()->recordStreamAssignment(
                    $item['sourceId'],
                    $streamCollection,
                    $item['groupId']
                );
            }
        }

        $this->connectExport->updateConnectItemsStatus($removedRecords, Attribute::STATUS_DELETE);
    }

    /**
     * @return ArrayCollection
     */
    public function registerConditionHandlers()
    {
        return new ArrayCollection([
            new SupplierConditionHandler(),
        ]);
    }
}