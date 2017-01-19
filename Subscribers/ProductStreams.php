<?php

namespace ShopwarePlugins\Connect\Subscribers;

use Shopware\Components\Model\ModelManager;
use ShopwarePlugins\Connect\Components\ConnectExport;
use ShopwarePlugins\Connect\Components\Helper;
use ShopwarePlugins\Connect\Components\ProductStream\ProductStreamService;

class ProductStreams extends BaseSubscriber
{
    /** @var ConnectExport */
    protected $connectExport;

    /** @var  Helper */
    protected $helper;

    /**
     * ProductStreams constructor.
     * @param ConnectExport $connectExport
     * @param Helper $helper
     */
    public function __construct(
        ConnectExport $connectExport,
        Helper $helper

    ) {
        parent::__construct();
        $this->connectExport = $connectExport;
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

                $sourceIds = $this->helper->getSourceIdsFromArticleId($articleId);
                $streamAssignments = $this->getProductStreamService()->prepareStreamsAssignments($streamId);

                //it can timeout if products are more than a 100
                if (count($sourceIds) > ProductStreamService::PRODUCT_LIMIT) {
                    return;
                }

                $this->connectExport->export($sourceIds, $streamAssignments);
                break;
            default:
                break;
        }
    }
}