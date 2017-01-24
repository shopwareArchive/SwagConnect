<?php

namespace ShopwarePlugins\Connect\Subscribers;

use Shopware\Components\Model\ModelManager;
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
            'Enlight_Controller_Action_PostDispatch_Backend_ProductStream' => 'extendBackendProductStream',
        );
    }

    public function getProductStreamService()
    {
        return $this->Application()->Container()->get('swagconnect.product_stream_service');
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
            case 'load':
                $this->registerMyTemplateDir();
                $this->registerMySnippets();

                $subject->View()->extendsTemplate(
                    'backend/product_stream/view/selected_list/connect_product.js'
                );
                break;
            case 'delete':
                $streamId = $request->get('id');

                if ($this->getProductStreamService()->isStreamExported($streamId)) {
                    $this->getSDK()->recordStreamDelete($streamId);
                }
                break;
            case 'addSelectedProduct':
                $streamId = $request->getParam('streamId');
                $articleId = $request->getParam('articleId');

                if (!$this->getProductStreamService()->isStreamExported($streamId)) {
                    return;
                }

                $autoUpdate = $this->config->getConfig('autoUpdateProducts', 1);
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
                } elseif ($autoUpdate == Config::UPDATE_CRON) {
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
}