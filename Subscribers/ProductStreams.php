<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Subscribers;

use Doctrine\Common\Collections\ArrayCollection;
use Enlight\Event\SubscriberInterface;
use Shopware\Connect\SDK;
use ShopwarePlugins\Connect\Bundle\SearchBundleDBAL\ConditionHandler\SupplierConditionHandler;
use Shopware\CustomModels\Connect\Attribute;
use ShopwarePlugins\Connect\Components\Config;
use ShopwarePlugins\Connect\Components\ConnectExport;
use ShopwarePlugins\Connect\Components\Helper;
use ShopwarePlugins\Connect\Components\ProductStream\ProductStreamsAssignments;
use ShopwarePlugins\Connect\Components\ProductStream\ProductStreamService;

class ProductStreams implements SubscriberInterface
{
    /**
     * @var ConnectExport
     */
    protected $connectExport;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Helper
     */
    protected $helper;

    /**
     * @var SDK
     */
    private $sdk;

    /**
     * @var string
     */
    private $pluginPath;

    /**
     * @var \Shopware_Components_Snippet_Manager
     */
    private $snippetManager;

    /**
     * @param ConnectExport $connectExport
     * @param Config $config
     * @param Helper $helper
     * @param SDK $sdk
     * @param $pluginPath
     * @param \Shopware_Components_Snippet_Manager $snippetManager
     */
    public function __construct(
        ConnectExport $connectExport,
        Config $config,
        Helper $helper,
        SDK $sdk,
        $pluginPath,
        \Shopware_Components_Snippet_Manager $snippetManager
    ) {
        $this->connectExport = $connectExport;
        $this->config = $config;
        $this->helper = $helper;
        $this->sdk = $sdk;
        $this->pluginPath = $pluginPath;
        $this->snippetManager = $snippetManager;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Action_PreDispatch_Backend_ProductStream' => 'preProductStream',
            'Enlight_Controller_Action_PostDispatch_Backend_ProductStream' => 'extendBackendProductStream',
            'Shopware_SearchBundleDBAL_Collect_Condition_Handlers' => 'registerConditionHandlers'
        ];
    }

    /**
     * @return ProductStreamService
     */
    public function getProductStreamService()
    {
        return Shopware()->Container()->get('swagconnect.product_stream_service');
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
                    $this->sdk->recordStreamDelete($streamId);
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
                $subject->View()->addTemplateDir($this->pluginPath . 'Views/', 'connect');
                $this->snippetManager->addConfigDir($this->pluginPath . 'Snippets/');

                $subject->View()->extendsTemplate(
                    'backend/product_stream/connect_app.js'
                );
                break;
            case 'load':
                $subject->View()->addTemplateDir($this->pluginPath . 'Views/', 'connect');
                $this->snippetManager->addConfigDir($this->pluginPath . 'Snippets/');

                $subject->View()->extendsTemplate(
                    'backend/product_stream/view/selected_list/connect_product.js'
                );
                $subject->View()->extendsTemplate(
                    'backend/product_stream/view/condition_list/connect_condition_panel.js'
                );
                $subject->View()->extendsTemplate(
                    'backend/product_stream/view/selected_list/connect_windows.js'
                );
                $subject->View()->extendsTemplate(
                    'backend/product_stream/view/list/connect_list.js'
                );
                break;
            case 'list':
                $subject->View()->data = $this->markConnectStreams(
                    $subject->View()->data
                );
                break;
            case 'detail':
                $streams = $this->markConnectStreams(
                    [$subject->View()->data]
                );
                $subject->View()->data = reset($streams);
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
                        ['cron_update' => 1],
                        ['article_id' => $articleId]
                    );
                }
                break;
            default:
                break;
        }
    }

    /**
     * @param int $streamId
     * @param ProductStreamsAssignments $assignments
     * @param array $articleIds
     */
    private function removeArticlesFromStream($streamId, $assignments, array $articleIds)
    {
        $removedRecords = [];
        $productStreamService = $this->getProductStreamService();
        $sourceIds = $this->helper->getArticleSourceIds($articleIds);
        $items = $this->connectExport->fetchConnectItems($sourceIds, false);

        foreach ($items as $item) {
            if ($productStreamService->allowToRemove($assignments, $streamId, $item['articleId'])) {
                $this->sdk->recordDelete($item['sourceId']);
                $removedRecords[] = $item['sourceId'];
            } else {
                //updates items with the new streams
                $streamCollection = $assignments->getStreamsByArticleId($item['articleId']);
                if (!$this->helper->isMainVariant($item['sourceId']) || !$streamCollection) {
                    continue;
                }

                //removes current stream from the collection
                unset($streamCollection[$streamId]);

                $this->sdk->recordStreamAssignment(
                    $item['sourceId'],
                    $streamCollection,
                    $item['groupId']
                );
            }
        }

        $this->connectExport->updateConnectItemsStatus($removedRecords, Attribute::STATUS_DELETE);
    }

    /**
     * @param $streams
     * @return array
     */
    private function markConnectStreams(array $streams)
    {
        $connectSteamIds = $this->getProductStreamService()->getConnectStreamIds();

        foreach ($streams as $index => $stream) {
            if (isset($stream['id'])) {
                $streams[$index]['isConnect'] = in_array($stream['id'], $connectSteamIds);
            }
        }

        return $streams;
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
