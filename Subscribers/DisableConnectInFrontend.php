<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Subscribers;

/**
 * The DisableConnectInFrontend subscriber is used, if the user's api key is not valid. In this case, connect products
 * cannot be ordered in the frontend.
 *
 * Class DisableConnectInFrontend
 */
class DisableConnectInFrontend extends BaseSubscriber
{
    public function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Action_PostDispatchSecure_Frontend_Detail' => 'disableBuyButtonForConnect',
        ];
    }

    /**
     * @event Enlight_Controller_Action_PostDispatch_Frontend_Detail
     *
     * @param \Enlight_Event_EventArgs $args
     */
    public function disableBuyButtonForConnect(\Enlight_Event_EventArgs $args)
    {
        /** \Shopware_Controllers_Frontend_Detail $controller */
        $controller = $args->getSubject();
        $view = $controller->View();

        $article = $view->getAssign('sArticle');

        if ($this->isConnectArticle($article['articleID'])) {
            $this->registerMyTemplateDir();
            if ($this->Application()->Container()->get('shop')->getTemplate()->getVersion() < 3) {
                $view->extendsTemplate('frontend/connect/detail.tpl');
            }
            $view->assign('hideConnect', true);
        }
    }

    /**
     * Not using the default helper-methods here, in order to keep this small and without any dependencies
     * to the SDK
     *
     * @param $id
     *
     * @return string
     */
    public function isConnectArticle($id)
    {
        $sql = 'SELECT shop_id FROM s_plugin_connect_items WHERE article_id = ? AND shop_id IS NOT NULL';

        return Shopware()->Db()->fetchOne($sql, [$id]);
    }
}
