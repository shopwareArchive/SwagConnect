<?php

namespace Shopware\Bepado\Subscribers;

/**
 * The DisableBepadoInFrontend subscriber is used, if the user's api key is not valid. In this case, bepado products
 * cannot be ordered in the frontend.
 *
 * Class DisableBepadoInFrontend
 * @package Shopware\Bepado\Subscribers
 */
class DisableBepadoInFrontend extends BaseSubscriber
{

    public function getSubscribedEvents()
    {
        return array(
            'Enlight_Controller_Action_PostDispatchSecure_Frontend_Detail' => 'disableBuyButtonForBepado'
        );
    }

    /**
     * @event Enlight_Controller_Action_PostDispatch_Frontend_Detail
     * @param \Enlight_Event_EventArgs $args
     */
    public function disableBuyButtonForBepado(\Enlight_Event_EventArgs $args)
    {
        /** \Shopware_Controllers_Frontend_Detail $controller */
        $controller = $args->getSubject();
        $view = $controller->View();

        $article = $view->getAssign('sArticle');

        if ($this->isBepadoArticle($article['articleID'])) {
            $this->registerMyTemplateDir();
            $view->extendsTemplate('frontend/bepado/detail.tpl');
            $view->assign('hideBepado', true);
        }
    }

    /**
     * Not using the default helper-methods here, in order to keep this small and without any dependencies
     * to the SDK
     *
     * @param $id
     * @return string
     */
    public function isBepadoArticle($id)
    {
        $sql = 'SELECT shop_id FROM s_plugin_bepado_items WHERE article_id = ? AND shop_id IS NOT NULL';
        return Shopware()->Db()->fetchOne($sql, array($id));
    }

}