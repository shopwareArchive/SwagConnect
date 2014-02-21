<?php

namespace Shopware\Bepado\Subscribers;
use Shopware\Models\Attribute\ArticlePrice;

/**
 * Class Article
 * @package Shopware\Bepado\Subscribers
 */
class Article extends BaseSubscriber
{
    public function getSubscribedEvents()
    {
        return array(
            'Shopware_Controllers_Backend_Article::getPrices::after' => 'onEnforcePriceAttributes',
            'Enlight_Controller_Action_PostDispatch_Backend_Article' => 'extendBackendArticle'
        );
    }

    /**
     * Make sure, that any price has a price attribute array, even if it is not in the database, yet
     *
     * @param \Enlight_Hook_HookArgs $args
     */
    public function onEnforcePriceAttributes(\Enlight_Hook_HookArgs $args)
    {
        $prices = $args->getReturn();

        foreach ($prices as &$price) {
            if ($price['attribute'] == null) {
                $model = new ArticlePrice();
                $price['attribute'] = Shopware()->Models()->toArray($model);
            }
        }
        
        $args->setReturn($prices);
    }


    /**
     * @event Enlight_Controller_Action_PostDispatch_Backend_Article
     * @param \Enlight_Event_EventArgs $args
     */
    public function extendBackendArticle(\Enlight_Event_EventArgs $args)
    {
        /** @var $subject \Enlight_Controller_Action */
        $subject = $args->getSubject();
        $request = $subject->Request();

        switch($request->getActionName()) {
            case 'index':
                $this->registerMyTemplateDir();
                $this->registerMySnippets();
                $subject->View()->extendsTemplate(
                    'backend/article/bepado.js'
                );
                break;
            case 'load':
                $this->registerMyTemplateDir();
                $this->registerMySnippets();
                $subject->View()->extendsTemplate(
                    'backend/article/model/attribute_bepado.js'
                );
                $subject->View()->extendsTemplate(
                    'backend/article/model/price_attribute_bepado.js'
                );
                $subject->View()->extendsTemplate(
                    'backend/article/view/detail/bepado_tab.js'
                );
                $subject->View()->extendsTemplate(
                    'backend/article/view/detail/prices_bepado.js'
                );
                $subject->View()->extendsTemplate(
                    'backend/article/controller/detail_bepado.js'
                );
                break;
            default:
                break;
        }
    }


}