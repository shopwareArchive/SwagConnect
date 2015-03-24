<?php

namespace Shopware\Bepado\Subscribers;
use Shopware\Bepado\Components\Config;
use Shopware\Bepado\Components\Exceptions\NoRemoteProductException;
use Shopware\Bepado\Components\Utils\BepadoOrderUtil;

/**
 * Loads various template extensions
 *
 * Class TemplateExtension
 * @package Shopware\Bepado\Subscribers
 */
class TemplateExtension extends BaseSubscriber
{

    public function getSubscribedEvents()
    {
        return array(
            'Enlight_Controller_Action_PostDispatch_Backend_Order' => 'onPostDispatchBackendOrder',
            'Enlight_Controller_Action_PostDispatch_Frontend_Detail' => 'addBepadoTemplateVariablesToDetail',
            'Enlight_Controller_Action_PostDispatch_Frontend' => 'addBepadoStyle'
        );
    }

    public function addBepadoStyle(\Enlight_Event_EventArgs $args)
    {
        /** @var $subject \Enlight_Controller_Action */
        $subject = $args->getSubject();

        $this->registerMyTemplateDir();
        $subject->View()->extendsTemplate('frontend/bepado/index/index.tpl');

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


        $bepadoOrderUtil = new BepadoOrderUtil();
        $result = $bepadoOrderUtil->getRemoteBepadoOrders($orderIds);

        foreach ($result as $bepadoOrder) {
            $bepadoOrderData[$bepadoOrder['orderID']] = $bepadoOrder;
        }

        $result = $bepadoOrderUtil->getLocalBepadoOrders($orderIds);

        foreach ($result as $bepadoOrder) {
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

        $products = $helper->getRemoteProducts($articleData['articleID']);

        if (empty($products)) {
            return;
        }

        $product = $products[0];
        if(empty($product->shopId)) {
            return;
        }

        // Fix prices for displaying
        foreach(array('price', 'purchasePrice', 'vat') as $name) {
            $product->$name = round($product->$name, 2);
        }

        $shop = $sdk->getShop($product->shopId);

        $modelsManager = Shopware()->Models();
        /** @var \Shopware\Bepado\Components\Config $configComponent */
        $configComponent = new Config($modelsManager);
        $view->assign(array(
            'bepadoProduct' => $product,
            'bepadoShop' => $shop,
            'bepadoShopInfo' => $configComponent->getConfig('detailShopInfo'),
            'bepadoNoIndex' => $configComponent->getConfig('detailProductNoIndex'),
            'shippingCostsPage' => $configComponent->getConfig('shippingCostsPage', 6, Shopware()->Shop()->getId() , 'general')
        ));
    }
}