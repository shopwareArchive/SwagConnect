<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Subscribers;

use Enlight\Event\SubscriberInterface;
use Enlight_Components_Db_Adapter_Pdo_Mysql;

/**
 * The DisableConnectInFrontend subscriber is used, if the user's api key is not valid. In this case, connect products
 * cannot be ordered in the frontend.
 */
class DisableConnectInFrontend implements SubscriberInterface
{
    /**
     * @var Enlight_Components_Db_Adapter_Pdo_Mysql
     */
    private $db;

    /**
     * @param Enlight_Components_Db_Adapter_Pdo_Mysql $db
     */
    public function __construct(Enlight_Components_Db_Adapter_Pdo_Mysql $db)
    {
        $this->db = $db;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Action_PostDispatchSecure_Frontend_Detail' => 'disableBuyButtonForConnect'
        ];
    }

    /**
     * @event Enlight_Controller_Action_PostDispatch_Frontend_Detail
     * @param \Enlight_Event_EventArgs $args
     */
    public function disableBuyButtonForConnect(\Enlight_Event_EventArgs $args)
    {
        /** @var \Shopware_Controllers_Frontend_Detail $controller */
        $controller = $args->getSubject();
        $view = $controller->View();

        $article = $view->getAssign('sArticle');
        if ($this->isConnectArticle($article['articleID'])) {
            $view->assign('hideConnect', true);
        }
    }

    /**
     * Not using the default helper-methods here, in order to keep this small and without any dependencies
     * to the SDK
     *
     * @param $id
     * @return string
     */
    private function isConnectArticle($id)
    {
        $sql = 'SELECT shop_id FROM s_plugin_connect_items WHERE article_id = ? AND shop_id IS NOT NULL';

        return $this->db->fetchOne($sql, [$id]);
    }
}
