<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Subscribers;

use Enlight\Event\SubscriberInterface;
use Shopware\Connect\SDK;
use ShopwarePlugins\Connect\Components\Config;
use ShopwarePlugins\Connect\Components\Helper;
use ShopwarePlugins\Connect\Components\Utils\ConnectOrderUtil;

/**
 * Loads various template extensions
 */
class TemplateExtension implements SubscriberInterface
{
    /**
     * @var string
     */
    private $pluginPath;

    /**
     * @var \Shopware_Components_Snippet_Manager
     */
    private $snippetManager;

    /**
     * @var SDK
     */
    private $sdk;
    /**
     * @var Helper
     */
    private $helper;

    /**
     * @param string $pluginPath
     * @param \Shopware_Components_Snippet_Manager $snippetManager
     * @param SDK $sdk
     * @param Helper $helper
     */
    public function __construct($pluginPath, \Shopware_Components_Snippet_Manager $snippetManager, SDK $sdk, Helper $helper)
    {
        $this->pluginPath = $pluginPath;
        $this->snippetManager = $snippetManager;
        $this->sdk = $sdk;
        $this->helper = $helper;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Action_PostDispatch_Backend_Order' => 'onPostDispatchBackendOrder',
            'Enlight_Controller_Action_PostDispatch_Frontend_Detail' => 'addConnectTemplateVariablesToDetail',
            'Enlight_Controller_Action_PostDispatch_Frontend' => 'addConnectStyle'
        ];
    }

    public function addConnectStyle(\Enlight_Event_EventArgs $args)
    {
        /** @var \Enlight_Controller_Action $subject */
        $subject = $args->getSubject();
        $subject->View()->addTemplateDir($this->pluginPath . 'Views/responsive/', 'connect');
    }

    /**
     * Extends the order backend module in order to show a special hint for connect products
     *
     * @param \Enlight_Event_EventArgs $args
     */
    public function onPostDispatchBackendOrder(\Enlight_Event_EventArgs $args)
    {
        /** @var $subject \Enlight_Controller_Action */
        $subject = $args->getSubject();
        $request = $subject->Request();

        switch ($request->getActionName()) {
            case 'load':
                $subject->View()->addTemplateDir($this->pluginPath . 'Views/', 'connect');
                $this->snippetManager->addConfigDir($this->pluginPath . 'Snippets/');

                $subject->View()->extendsTemplate(
                    'backend/order/view/connect.js'
                );

                $subject->View()->extendsTemplate(
                    'backend/order/controller/connect_main.js'
                );

                break;

            case 'getList':
                $subject->View()->data = $this->markConnectOrders(
                    $subject->View()->data
                );

                break;

            default:
                break;
        }
    }

    /**
     * Mark Orders as Connect Orders for view purposes.
     *
     * @param array $data
     * @return array
     */
    private function markConnectOrders($data)
    {
        $orderIds = array_map(function ($orderView) {
            return (int) $orderView['id'];
        }, $data);

        if (!$orderIds) {
            return $data;
        }

        $connectOrderData = [];


        $connectOrderUtil = new ConnectOrderUtil();
        $result = $connectOrderUtil->getRemoteConnectOrders($orderIds);

        foreach ($result as $connectOrder) {
            $connectOrderData[$connectOrder['orderID']] = $connectOrder;
        }

        $result = $connectOrderUtil->getLocalConnectOrders($orderIds);

        foreach ($result as $connectOrder) {
            $connectOrderData[$connectOrder['orderID']] = $connectOrder;
        }

        if (!$connectOrderData) {
            return $data;
        }

        $shopNames = [];

        foreach ($data as $idx => $order) {
            if (! isset($connectOrderData[$order['id']])) {
                continue;
            }

            $result = $connectOrderData[$order['id']];

            $data[$idx]['connectShopId'] = $result['connect_shop_id'];
            $data[$idx]['connectOrderId'] = $result['connect_order_id'];

            if (!isset($shopNames[$result['connect_shop_id']])) {
                $shopNames[$result['connect_shop_id']] = $this->sdk->getShop($result['connect_shop_id'])->name;
            }

            $data[$idx]['connectShop'] = $shopNames[$result['connect_shop_id']];
        }

        return $data;
    }

    /**
     * Event listener method for the frontend detail page. Will add connect template variables if the current product
     * is a connect product.
     *
     * @event Enlight_Controller_Action_PostDispatch_Frontend_Detail
     * @param \Enlight_Event_EventArgs $args
     */
    public function addConnectTemplateVariablesToDetail(\Enlight_Event_EventArgs $args)
    {
        /** @var $action \Enlight_Controller_Action */
        $action = $args->getSubject();
        $view = $action->View();

        $action->View()->addTemplateDir($this->pluginPath . 'Views/responsive', 'connect');

        $articleData = $view->getAssign('sArticle');
        if (empty($articleData['articleID'])) {
            return;
        }

        if ($this->helper->isRemoteArticleDetail($articleData['articleDetailsID']) === false) {
            return;
        }

        $shopProductId = $this->helper->getShopProductId($articleData['articleDetailsID']);
        $products = $this->helper->getRemoteProducts([$shopProductId->sourceId], $shopProductId->shopId);

        if (empty($products)) {
            return;
        }

        $product = reset($products);
        if (empty($product->shopId)) {
            return;
        }

        // Fix prices for displaying
        foreach (['price', 'purchasePrice', 'vat'] as $name) {
            $product->$name = round($product->$name, 2);
        }

        $shop = $this->sdk->getShop($product->shopId);

        $modelsManager = Shopware()->Models();
        /** @var \ShopwarePlugins\Connect\Components\Config $configComponent */
        $configComponent = new Config($modelsManager);
        $view->assign([
            'connectProduct' => $product,
            'connectShop' => $shop,
            'connectShopInfo' => $configComponent->getConfig('detailShopInfo'),
            'connectNoIndex' => $configComponent->getConfig('detailProductNoIndex'),
        ]);
    }
}
