<?php

namespace Shopware\Bepado\Subscribers;

class TemplateExtension extends BaseSubscriber
{

    public function getSubscribedEvents()
    {
        return array(
            'Enlight_Controller_Action_PostDispatch_Backend_Order' => 'onPostDispatchBackendOrder',
            'Enlight_Controller_Action_PostDispatch_Frontend_Detail' => 'addBepadoTemplateVariablesToDetail',
            'Enlight_Controller_Action_PostDispatch_Backend_ArticleList' => 'extentBackendArticleList',
            'Enlight_Controller_Action_PostDispatch_Backend_Article' => 'extendBackendArticle',
            'Enlight_Controller_Action_PostDispatch_Backend_Index' => 'injectBackendBepadoMenuEntry',
            'Enlight_Controller_Action_PostDispatch_Frontend_Search' => 'injectCloudSearchResults'
        );
    }

    /**
     * Extends the order backend module in order to show a special hint for bepado products
     *
     * @param \Enlight_Event_EventArgs $args
     */
    public function onPostDispatchBackendOrder(\Enlight_Event_EventArgs $args)
    {
        /** @var $subject \Enlight_Controller_Action */
        $subject = $args->getSubject();
        $request = $subject->Request();

        switch($request->getActionName()) {
            case 'load':
                $this->registerMyTemplateDir();
                $this->registerMySnippets();
                $subject->View()->extendsTemplate(
                    'backend/order/bepado.js'
                );

                break;

            case 'getList':
                $subject->View()->data = $this->markBepadoOrders(
                    $subject->View()->data
                );

                break;

            default:
                break;
        }
    }

    /**
     * Mark Orders as Bepado Orders for view purposes.
     *
     * @param array $data
     * @return array
     */
    private function markBepadoOrders($data)
    {
        $sdk = $this->getSDK();

        $orderIds = array_map(function ($orderView) {
            return (int)$orderView['id'];
        }, $data);

        if (!$orderIds) {
            return $data;
        }

        $bepadoOrderData = array();

        $sql = 'SELECT orderID, bepado_shop_id, bepado_order_id FROM s_order_attributes WHERE orderID IN (' . implode(', ', $orderIds) . ')';

        foreach (Shopware()->Db()->fetchAll($sql) as $bepadoOrder) {
            $bepadoOrderData[$bepadoOrder['orderID']] = $bepadoOrder;
        }

        if (!$bepadoOrderData) {
            return $data;
        }

        $shopNames = array();

        foreach($data as $idx => $order) {
            if ( ! isset($bepadoOrderData[$order['id']])) {
                continue;
            }

            $result = $bepadoOrderData[$order['id']];

            $data[$idx]['bepadoShopId'] = $result['bepado_shop_id'];
            $data[$idx]['bepadoOrderId'] = $result['bepado_order_id'];

            if (!isset($shopNames[$result['bepado_shop_id']])) {
                $shopNames[$result['bepado_shop_id']] = $sdk->getShop($result['bepado_shop_id'])->name;
            }

            $data[$idx]['bepadoShop'] = $shopNames[$result['bepado_shop_id']];
        }

        return $data;
    }

    /**
     * Event listener method for the frontend detail page. Will add bepado template variables if the current product
     * is a bepado product.
     *
     * @event Enlight_Controller_Action_PostDispatch_Frontend_Detail
     * @param \Enlight_Event_EventArgs $args
     */
    public function addBepadoTemplateVariablesToDetail(\Enlight_Event_EventArgs $args)
    {
        /** @var $action \Enlight_Controller_Action */
        $action = $args->getSubject();
        $view = $action->View();
        $sdk = $this->getSDK();
        $helper = $this->getHelper();

        $this->registerMyTemplateDir();
        $view->extendsTemplate('frontend/bepado/detail.tpl');

        $articleData = $view->getAssign('sArticle');
        if(empty($articleData['articleID'])) {
            return;
        }

        $product = $helper->getProductById($articleData['articleID']);
        if(empty($product->shopId)) {
            return;
        }
        $shop = $sdk->getShop($product->shopId);

        $view->assign(array(
            'bepadoProduct' => $product,
            'bepadoShop' => $shop,
            'bepadoShopInfo' => $this->Config()->get('detailShopInfo'),
            'bepadoNoIndex' => $this->Config()->get('detailProductNoIndex')
        ));
    }


    /**
     * Extends the product list in the backend in order to have a special hint for bepado products
     *
     * @event Enlight_Controller_Action_PostDispatch_Backend_ArticleList
     * @param \Enlight_Event_EventArgs $args
     */
    public function extentBackendArticleList(\Enlight_Event_EventArgs $args)
    {
        /** @var $subject \Enlight_Controller_Action */
        $subject = $args->getSubject();
        $request = $subject->Request();

        switch($request->getActionName()) {
            case 'load':
                $this->registerMyTemplateDir();
                $this->registerMySnippets();
                $subject->View()->extendsTemplate(
                    'backend/article_list/bepado.js'
                );
                break;
            case 'list':
                $subject->View()->data = $this->markBepadoProducts(
                    $subject->View()->data
                );
                break;
            default:
                break;
        }
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
            case 'load':
                $this->registerMyTemplateDir();
                $this->registerMySnippets();
                $subject->View()->extendsTemplate(
                    'backend/article/bepado.js'
                );
                break;
            default:
                break;
        }
    }

    /**
     * Helper method which adds an additional 'bepado' field to article objects in order to indicate
     * if they are bepado articles or not
     *
     * @param $data
     * @return mixed
     */
    private function markBepadoProducts($data)
    {
        $articleIds = array_map(function ($row) {
            return (int)$row['articleId'];
        }, $data);

        $sql = 'SELECT articleID FROM s_articles_attributes WHERE articleID IN (' . implode(', ', $articleIds) . ') AND bepado_source_id IS NOT NULL';
        $bepadoArticleIds = array_map(function ($row) {
            return $row['articleID'];
        }, Shopware()->Db()->fetchAll($sql));

        foreach($data as $idx => $row) {
            $data[$idx]['bepado'] = in_array($row['articleId'], $bepadoArticleIds);
        }

        return $data;
    }

    /**
     * Callback method for the Backend/Index postDispatch event.
     * Will add the bepado sprite to the menu
     *
     * @event Enlight_Controller_Action_PostDispatch_Backend_Index
     * @param \Enlight_Event_EventArgs $args
     * @returns boolean|void
     */
    public function injectBackendBepadoMenuEntry(\Enlight_Event_EventArgs $args)
    {
        /** @var $action \Enlight_Controller_Action */
        $action = $args->getSubject();
        $request = $action->Request();
        $response = $action->Response();
        $view = $action->View();

        if (!$request->isDispatched() || $response->isException() || !$view->hasTemplate()
        ) {
            return;
        }

        $view->addTemplateDir($this->Path() . 'Views/');
        $view->extendsTemplate('backend/bepado/menu_entry.tpl');
    }

    /**
     * Event listener method for frontend searches
     *
     * @event Enlight_Controller_Action_PostDispatch_Frontend_Search
     * @param \Enlight_Event_EventArgs $args
     */
    public function injectCloudSearchResults(\Enlight_Event_EventArgs $args)
    {
        /** @var $action \Enlight_Controller_Action */
        $action = $args->getSubject();
        $request = $action->Request();
        $view = $action->View();

        if(!$request->isDispatched() || $request->getActionName() != 'defaultSearch') {
            return;
        }
        if(!$this->Config()->get('cloudSearch')) {
            return;
        }
        if(!empty($view->sSearchResults['sArticlesCount'])) {
            return;
        }
        if(empty($view->sRequests['sSearch'])) {
            return;
        }

        $action->redirect(array(
            'controller' => 'bepado',
            'action' => 'search',
            'query' => $view->sRequests['sSearch']
        ));
    }

}