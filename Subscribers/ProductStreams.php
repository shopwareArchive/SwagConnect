<?php

namespace ShopwarePlugins\Connect\Subscribers;

use Shopware\Components\Model\ModelManager;

class ProductStreams extends BaseSubscriber
{
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

        switch($request->getActionName()) {
            case 'load':
                $this->registerMyTemplateDir();
                $this->registerMySnippets();

                $subject->View()->extendsTemplate(
                    'backend/product_stream/controller/connect_main.js'
                );
                break;
            case 'delete':
                $streamId = $request->get('id');

                if ($this->getProductStreamService()->isStreamExported($streamId)) {
                    $this->getSDK()->recordStreamDelete($streamId);
                }
                break;
            case 'update':
                $streamId = $request->get('id');
                    $data = $subject->View()->data;
                    $data['isExported'] = $this->getProductStreamService()->isStreamExported($streamId);
                    $subject->View()->data = $data;
                break;
            default:
                break;
        }
    }
}