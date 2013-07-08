<?php
/**
 * Shopware 4.0
 * Copyright © 2013 shopware AG
 *
 * According to our dual licensing model, this program can be used either
 * under the terms of the GNU Affero General Public License, version 3,
 * or under a proprietary license.
 *
 * The texts of the GNU Affero General Public License with an additional
 * permission and of our proprietary license can be found at and
 * in the LICENSE file you have received along with this program.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * "Shopware" is a registered trademark of shopware AG.
 * The licensing of the program under the AGPLv3 does not imply a
 * trademark license. Therefore any rights, title and interest in
 * our trademarks remain entirely with us.
 */

/**
 * @category  Shopware
 * @package   Shopware\Plugins\SwagBepado
 * @copyright Copyright (c) 2013, shopware AG (http://www.shopware.de)
 * @author    Heiner Lohaus
 */
final class Shopware_Plugins_Backend_SwagBepado_Bootstrap extends Shopware_Components_Plugin_Bootstrap
{
    /**
     * Returns the current version of the plugin.
     *
     * @return string
     */
    public function getVersion()
    {
        return '1.2.1';
    }

    /**
     * Returns a nice name for plugin manager list
     *
     * @return string
     */
    public function getLabel()
    {
        return 'bepado';
    }

    /**
     * @return array
     */
    public function getInfo()
    {
        return array(
            'version' => $this->getVersion(),
            'label' => $this->getLabel(),
            //'description' => file_get_contents($this->Path() . 'info.txt'),
            'link' => 'http://www.shopware.de/',
        );
    }

	/**
	 * Install plugin method
	 *
	 * @return bool
	 */
	public function install()
	{	
        $this->createMyMenu();
        $this->createMyForm();
        $this->createMyEvents();
        $this->createMyTables();
        $this->createMyAttributes();

	 	return true;
	}

    private function createMyAttributes()
    {
        $this->Application()->Models()->addAttribute(
            's_articles_attributes',
            'bepado', 'shop_id',
            'varchar(255)'
        );
        $this->Application()->Models()->addAttribute(
            's_articles_attributes',
            'bepado', 'source_id',
            'varchar(255)'
        );
        $this->Application()->Models()->addAttribute(
            's_articles_attributes',
            'bepado', 'export_status',
            'text'
        );
        $this->Application()->Models()->addAttribute(
            's_articles_attributes',
            'bepado', 'export_message',
            'text'
        );

        $this->Application()->Models()->addAttribute(
            's_order_attributes',
            'bepado', 'shop_id',
            'int(11)'
        );
        $this->Application()->Models()->addAttribute(
            's_order_attributes',
            'bepado', 'order_id',
            'int(11)'
        );

        $this->Application()->Models()->addAttribute(
            's_articles_attributes',
            'bepado', 'categories',
            'text'
        );
        $this->Application()->Models()->addAttribute(
           's_categories_attributes',
           'bepado', 'mapping',
           'text'
        );

        $this->Application()->Models()->generateAttributeModels(array(
            's_articles_attributes',
            's_categories_attributes',
            's_order_details_attributes',
            's_order_basket_attributes',
        ));
    }

    private function createMyMenu()
    {
        $parent = $this->Menu()->findOneBy(array('label' => 'Marketing'));
        $this->createMenuItem(array(
            'label' => $this->getLabel(),
            'controller' => 'Bepado',
            'action' => 'Index',
            'class' => 'bepado-icon',
            'active' => 1,
            'parent' => $parent
        ));
    }

    private function createMyForm()
    {
        $form = $this->Form();

        $form->setElement('text', 'apiKey', array(
            'label' => 'API Key',
            'description' => '',
            'required' => true,
            'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP,
            'uniqueId' => 'apiKey'
        ));
        $form->setElement('button', 'verifyApiKey', array(
            'label' => '<strong>API Key testen</strong>',
            'handler' => "function(btn) {
                var apiField = btn.up('form').down('textfield[uniqueId=apiKey]'),
                    apiKey = apiField.getValue(),
                    title = btn.up('window').title;
                Ext.Ajax.request({
                    scope: this,
                    url: window.location.pathname + 'bepado/verifyApiKey',
                    success: function(result, request) {
                        var response = Ext.JSON.decode(result.responseText);
                        apiField.setFieldStyle('background-color', response.success ? 'green' : 'red');
                        if(response.message) {
                            Shopware.Notification.createGrowlMessage(
                                btn.title,
                                response.message,
                                title
                            );
                        }
                    },
                    failure: function() { },
                    params: { apiKey: apiKey }
                });
            }"
        ));
        $form->setElement('select', 'exportPriceGroup', array(
            'label' => 'Export-Preisgruppe',
            'store' => 'base.CustomerGroup'
        ));
        $form->setElement('boolean', 'importCreateCategories', array(
            'label' => 'Kategorien beim Import automatisch erzeugen',
            'value' => true
        ));
        $form->setElement('boolean', 'detailProductNoIndex', array(
            'label' => 'Ein "noindex"-Meta-Tag bei Bepado-Produkten setzten',
            'value' => true
        ));
        $form->setElement('boolean', 'detailShopInfo', array(
            'label' => 'Auf der Detailseite auf Marktplatz-Artikel hinweisen',
            'value' => true
        ));
        $form->setElement('boolean', 'checkoutShopInfo', array(
            'label' => 'Im Warenkorb auf Marktplatz-Artikel hinweisen',
            'value' => true
        ));
        $form->setElement('boolean', 'cloudSearch', array(
            'label' => 'Cloud-Search aktivieren'
        ));
        $form->setElement('text', 'productDescriptionField', array(
            'label' => 'Feld für Produktbeschreibungen'
        ));
    }

    private function createMyEvents()
    {
        $this->subscribeEvent(
            'Enlight_Bootstrap_InitResource_BepadoSDK',
            'onInitResourceSDK'
        );

        $this->subscribeEvent(
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_Bepado',
            'onGetControllerPathBackend'
        );

        $this->subscribeEvent(
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_BepadoGateway',
            'onGetControllerPathGateway'
        );

        $this->subscribeEvent(
            'Enlight_Controller_Dispatcher_ControllerPath_Frontend_Bepado',
            'onGetControllerPathFrontend'
        );

        $this->subscribeEvent(
            'Enlight_Controller_Action_PostDispatch_Frontend_Checkout',
            'onPostDispatchFrontendCheckout'
        );

        $this->subscribeEvent(
            'Enlight_Controller_Action_PreDispatch_Frontend_Checkout',
            'onPreDispatchFrontendCheckout'
        );

        $this->subscribeEvent(
            'Enlight_Controller_Action_PostDispatch_Frontend_Detail',
            'onPostDispatchFrontendDetail'
        );

        $this->subscribeEvent(
            'sOrder::sSaveOrder::after',
            'onSaveOrder'
        );

        $this->subscribeEvent(
            'Enlight_Controller_Action_PostDispatch_Frontend_Search',
            'onPostDispatchFrontendSearch'
        );

        $this->subscribeEvent(
            'Enlight_Controller_Action_PostDispatch_Backend_ArticleList',
            'onPostDispatchBackendArticleList'
        );

        $this->subscribeEvent(
            'Enlight_Controller_Action_PostDispatch_Backend_Order',
            'onPostDispatchBackendOrder'
        );

	    $this->subscribeEvent(
		    'Enlight_Controller_Action_PostDispatch_Backend_Index',
		    'onPostDispatch'
	    );
    }

    private function createMyTables()
    {
        $queries = array("
            CREATE TABLE IF NOT EXISTS `bepado_change` (
              `c_source_id` varchar(64) NOT NULL,
              `c_operation` char(8) NOT NULL,
              `c_revision` decimal(20,10) NOT NULL,
              `c_product` longblob,
              `changed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              UNIQUE KEY `c_revision` (`c_revision`),
              KEY `c_source_id` (`c_source_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;", "
           CREATE TABLE IF NOT EXISTS `bepado_data` (
              `d_key` varchar(32) NOT NULL,
              `d_value` varchar(256) NOT NULL,
              `changed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`d_key`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;", "
            CREATE TABLE IF NOT EXISTS `bepado_product` (
              `p_source_id` varchar(64) NOT NULL,
              `p_hash` varchar(64) NOT NULL,
              `changed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`p_source_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;", "
            CREATE TABLE IF NOT EXISTS `bepado_reservations` (
              `r_id` varchar(32) NOT NULL,
              `r_state` varchar(12) NOT NULL,
              `r_order` longblob NOT NULL,
              `changed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`r_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;","
            CREATE TABLE IF NOT EXISTS `bepado_shop_config` (
              `s_shop` varchar(32) NOT NULL,
              `s_config` mediumblob NOT NULL,
              `changed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`s_shop`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");

        foreach ($queries as $query) {
            Shopware()->Db()->exec($query);
        }
    }
	
	/**
	 * Uninstall plugin method
	 *
	 * @return bool
	 */
	public function uninstall()
	{
        $this->removeMyAttributes();
        return true;
	}

    /**
     * @return bool
     */
    public function update()
    {
        $this->createMyEvents();
        $this->createMyForm();
        return true;
    }

    private function removeMyAttributes()
    {
        try {
            $this->Application()->Models()->removeAttribute(
                's_articles_attributes',
                'bepado', 'shop_id'
            );
            $this->Application()->Models()->removeAttribute(
                's_articles_attributes',
                'bepado', 'source_id'
            );
            $this->Application()->Models()->removeAttribute(
                's_articles_attributes',
                'bepado', 'export_status'
            );
            $this->Application()->Models()->removeAttribute(
                's_articles_attributes',
                'bepado', 'export_message'
            );

            $this->Application()->Models()->removeAttribute(
                's_order_details_attributes',
                'bepado', 'reservation_id'
            );
            $this->Application()->Models()->removeAttribute(
                's_order_basket_attributes',
                'bepado', 'reservation_id'
            );

            $this->Application()->Models()->removeAttribute(
                's_articles_attributes',
                'bepado', 'categories'
            );
            $this->Application()->Models()->removeAttribute(
                's_categories_attributes',
                'bepado', 'mapping'
            );

            $this->Application()->Models()->generateAttributeModels(array(
                's_articles_attributes',
                's_categories_attributes',
                's_order_details_attributes',
                's_order_basket_attributes',
            ));
        } catch(Exception $e) { }

    }

    private function registerMyTemplateDir()
    {
        $this->Application()->Template()->addTemplateDir(
            $this->Path() . 'Views/', 'bepado'
        );
    }

    private function registerMyLibrary()
    {
        $this->Application()->Loader()->registerNamespace(
            'Bepado',
            $this->Path() . 'Library/Bepado/'
        );
        $this->Application()->Loader()->registerNamespace(
            'Shopware\\Bepado',
            $this->Path() . 'Library/Shopware/Bepado/'
        );
    }

    private function registerMySnippets()
    {
        $this->Application()->Snippets()->addConfigDir(
            $this->Path() . 'Snippets/'
        );
    }

    /**
     * @param   Enlight_Event_EventArgs $args
     * @return  Bepado\SDK\SDK
     */
    public function onInitResourceSDK(Enlight_Event_EventArgs $args)
    {
        $this->registerMyLibrary();
        /** @var $connection PDO */
        $connection = $this->Application()->Db()->getConnection();
        $manager = $this->Application()->Models();
        $front = $this->Application()->Front();
        $helper = new \Shopware\Bepado\Helper($manager);
        $apiKey = $this->Config()->get('apiKey');

        return new Bepado\SDK\SDK(
            $apiKey,
            $front->Router()->assemble(array(
                'module' => 'backend',
                'controller' => 'bepado_gateway',
                'fullPath' => true
            )),
            new \Bepado\SDK\Gateway\PDO($connection),
            new \Shopware\Bepado\ProductToShop(
                $helper,
                $manager
            ),
            new \Shopware\Bepado\ProductFromShop(
                $helper,
                $manager
            )
        );
    }

    private $helper, $sdk;

    /**
     * @return Bepado\SDK\SDK
     */
    private function getSDK()
    {
        if($this->sdk === null) {
            $this->sdk = $this->Application()->Bootstrap()->getResource('BepadoSDK');
        }
        return $this->sdk;
    }

    /**
     * @return \Shopware\Bepado\Helper
     */
    protected function getHelper()
    {
        if($this->helper === null) {
            $this->helper = new \Shopware\Bepado\Helper(
                $this->Application()->Models(),
                $this->getImagePath(),
                $this->Config()->get('productDescriptionField')
            );
        }
        return $this->helper;
    }

    /**
     * @return string
     */
    protected function getImagePath()
    {
        $request = $this->Application()->Front()->Request();
        $imagePath = $request->getScheme() . '://'
                   . $request->getHttpHost() . $request->getBasePath();
        $imagePath .= '/media/image/';
        return $imagePath;
    }

    /**
     * @param   Enlight_Event_EventArgs $args
     * @return  string
     * @Enlight\Event Enlight_Controller_Dispatcher_ControllerPath_Backend_Bepado
     */
    public function onGetControllerPathBackend(Enlight_Event_EventArgs $args)
    {
        $this->registerMyTemplateDir();
        $this->Application()->Snippets()->addConfigDir(
            $this->Path() . 'Snippets/'
        );
        return $this->Path() . 'Controllers/Backend/Bepado.php';
    }

    /**
     * @param   Enlight_Event_EventArgs $args
     * @return  string
     */
    public function onGetControllerPathGateway(Enlight_Event_EventArgs $args)
    {
        return $this->Path() . 'Controllers/Backend/BepadoGateway.php';
    }

    /**
     * @param   Enlight_Event_EventArgs $args
     * @return  string
     */
    public function onGetControllerPathFrontend(Enlight_Event_EventArgs $args)
    {
        $this->registerMyTemplateDir();
        return $this->Path() . 'Controllers/Frontend/Bepado.php';
    }

    private function getDeliveryAddress($userData)
    {
        $shippingData = $userData['shippingaddress'];
        $address = new Bepado\SDK\Struct\Address();
        $address->zip = $shippingData['zipcode'];
        $address->city = $shippingData['city'];
        $address->country = $userData['additional']['countryShipping']['iso3'];
        if(!empty($userData['additional']['stateShipping']['shortcode'])) {
            $address->state = $userData['additional']['stateShipping']['shortcode'];
        }
        $address->name = $shippingData['firstname'] . ' ' . $shippingData['lastname'];
        if(!empty($shippingData['company'])) {
            $address->company = $shippingData['company'];
        }
        $address->line1 = $shippingData['street'] . ' ' . $shippingData['streetnumber'];
        return $address;
    }

    /**
     * Event listener method
     *
     * @param Enlight_Event_EventArgs $args
     * @return void
     */
    public function onPostDispatchFrontendCheckout(Enlight_Event_EventArgs $args)
    {
        /** @var $action Enlight_Controller_Action */
        $action = $args->getSubject();
        $view = $action->View();
        $request = $action->Request();
        $actionName = $request->getActionName();

        if(!in_array($actionName, array('confirm', 'cart'))) {
            return;
        }
        if(empty($view->sBasket) || !$request->isDispatched()) {
            return;
        }
        if(!empty($view->sOrderNumber)) {
            return;
        }

        $this->registerMyTemplateDir();
        $view->extendsTemplate('frontend/bepado/checkout.tpl');

        $sdk = $this->getSDK();
        $helper = $this->getHelper();

        $bepadoContent = array();
        $bepadoProducts = array();

        $basket = $view->sBasket;
        foreach ($basket['content'] as $key => $row) {
            if(!empty($row['mode'])) {
                continue;
            }
            $product = $helper->getProductById($row['articleID']);
            if($product === null || $product->shopId === null) {
                continue;
            }
            $bepadoProducts[$product->shopId][$product->sourceId] = $product;
            $bepadoContent[$product->shopId][$product->sourceId] = $row;

            if($actionName == 'cart') {
                unset($basket['content'][$key]);
            }
        }
        $bepadoShops = array();
        foreach($bepadoContent as $shopId => $items) {
            $bepadoShops[$shopId] = $sdk->getShop($shopId);
        }

        $bepadoShippingCosts = array();
        foreach($bepadoProducts as $shopId => $products) {
            $bepadoShippingCosts[$shopId] = $sdk->calculateShippingCosts($products);
        }

        if(($bepadoMessages = $view->getAssign('bepadoMessages')) === null) {
            $bepadoMessages = array();
            foreach($bepadoProducts as $shopId => $products) {
                /** @var $response Bepado\SDK\Struct\Message */
                $response = $sdk->checkProducts($products);
                if($response !== true) {
                    $bepadoMessages[$shopId] = $response;
                }
            }
        }

        if(empty($basket['content'])) {
            reset($bepadoContent);
            $shopId = current(array_keys($bepadoContent));
            $basket['content'] = $bepadoContent[$shopId];
            $view->shopId = $shopId;
            unset($bepadoContent[$shopId]);
        }

        $shippingCosts = array_sum($bepadoShippingCosts);
        $shippingCostsOrg = $basket['sShippingcosts'];
        $basket['sShippingcosts'] += $shippingCosts;
        $basket['AmountNumeric'] += $shippingCosts;
        $basket['AmountNetNumeric'] += $shippingCosts;
        $basket['sAmount'] += $shippingCosts;
        if(!empty($basket['sAmountWithTax'])) {
            $basket['sAmountWithTax'] += $shippingCosts;
        }
        $newVariables = array(
            'sBasket' => $basket,
            'sShippingcosts' => $basket['sShippingcosts'],
            'sAmount' => $basket['sAmount'],
            'sAmountWithTax' => $basket['sAmountWithTax'],
            'sAmountNet' => $basket['AmountNetNumeric']
        );
        $view->assign($newVariables);

        if($actionName == 'confirm') {
            $session = Shopware()->Session();
            /** @var $variables ArrayObject */
            $variables = $session->offsetGet('sOrderVariables');
            $variables->exchangeArray(array_merge(
                $variables->getArrayCopy(), $newVariables
            ));
            $session->offsetSet('sOrderVariables', $variables);
        }

        $view->assign(array(
            'bepadoContent' => $bepadoContent,
            'bepadoShops' => $bepadoShops,
            'bepadoMessages' => $bepadoMessages,
            'bepadoShippingCosts' => $bepadoShippingCosts,
            'bepadoShippingCostsOrg' => $shippingCostsOrg,
            'bepadoShopInfo' => $this->Config()->get('checkoutShopInfo'),
        ));
    }

    /**
     * Event listener method
     *
     * @param Enlight_Event_EventArgs $args
     */
    public function onPreDispatchFrontendCheckout(Enlight_Event_EventArgs $args)
    {
        /** @var $action Enlight_Controller_Action */
        $action = $args->getSubject();
        $request = $action->Request();
        $view = $action->View();
        $session = Shopware()->Session();
        $sdk = $this->getSDK();
        $helper = $this->getHelper();

        if($request->getActionName() != 'finish') {
            return;
        }
        if(empty($session['sOrderVariables'])) {
			return;
		}

        $order = new Bepado\SDK\Struct\Order();
        $order->products = array();
        $userData = $session['sOrderVariables']['sUserData'];
        $order->deliveryAddress = $this->getDeliveryAddress($userData);

        $basket = $session['sOrderVariables']['sBasket'];
        foreach ($basket['content'] as $row) {
            if(!empty($row['mode'])) {
                continue;
            }
            $product = $helper->getProductById($row['articleID']);
            if($product === null || $product->shopId === null) {
                continue;
            }

            $orderItem = new Bepado\SDK\Struct\OrderItem();
            $orderItem->product = $product;
            $orderItem->count = (int)$row['quantity'];
            $order->products[] = $orderItem;
        }

        /** @var $reservation Bepado\SDK\Struct\Reservation */
        $reservation = $sdk->reserveProducts($order);
        if(!empty($reservation->messages)) {
            $view->assign('bepadoMessages', $reservation->messages);
            $action->forward('confirm');
        } else {
            Shopware()->Session()->BepadoReservation = $reservation;
        }
    }

    /**
     * Event listener method
     *
     * @param Enlight_Event_EventArgs $args
     */
    public function onPostDispatchFrontendDetail(Enlight_Event_EventArgs $args)
    {
        /** @var $action Enlight_Controller_Action */
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
     * @param Enlight_Hook_HookArgs $args
     */
    public function onSaveOrder(Enlight_Hook_HookArgs $args)
    {
        $orderNumber = $args->getReturn();
        $sdk = $this->getSDK();

        if (empty($orderNumber)) {
            return;
        }

        $reservation = Shopware()->Session()->BepadoReservation;
        if($reservation !== null) {
            $sdk->checkout($reservation, $orderNumber);
        }
    }

    /**
     * Event listener method
     *
     * @param Enlight_Event_EventArgs $args
     */
    public function onPostDispatchFrontendSearch(Enlight_Event_EventArgs $args)
    {
        /** @var $action Enlight_Controller_Action */
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

    /**
     * @param Enlight_Event_EventArgs $args
     */
    public function onPostDispatchBackendArticleList(Enlight_Event_EventArgs $args)
    {
        /** @var $subject Enlight_Controller_Action */
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
                $data = $subject->View()->data;
                foreach($data as &$row) {
                    $sql = 'SELECT 1 FROM s_articles_attributes WHERE articleID = ? AND bepado_source_id IS NOT NULL';
                    $row['bepado'] = (bool)Shopware()->Db()->fetchOne($sql, array($row['articleId']));
                }
                $subject->View()->data = $data;
                break;
            default:
                break;
        }
    }

    /**
     * @param Enlight_Event_EventArgs $args
     */
    public function onPostDispatchBackendOrder(Enlight_Event_EventArgs $args)
    {
        /** @var $subject Enlight_Controller_Action */
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
                $data = $subject->View()->data;
                $sdk = $this->getSDK();
                foreach($data as &$row) {
                    $sql = 'SELECT bepado_shop_id, bepado_order_id FROM s_order_attributes WHERE orderID = ?';
                    $result = Shopware()->Db()->fetchRow($sql, array($row['id']));
                    if(!empty($result)) {
                        $row['bepadoShopId'] = $result['bepado_shop_id'];
                        $row['bepadoShop'] = $sdk->getShop($result['bepado_shop_id'])->name;
                        $row['bepadoOrderId'] = $result['bepado_order_id'];
                    }
                }
                $subject->View()->data = $data;
                break;
            default:
                break;
        }
    }

	/**
	 * Event listener method which will be triggered when the backend will be rendered.
	 *
	 * The method provides the menu entry item.
	 *
	 * @param Enlight_Event_EventArgs $args
	 * @returns boolean|void
	 */
	public function onPostDispatch(Enlight_Event_EventArgs $args)
    {
        $action = $args->getSubject();
        $request = $action->Request();
        $response = $action->Response();
        $view = $action->View();

        if (!$request->isDispatched()
            || $response->isException()
            || $request->getModuleName() != 'backend'
        ) {
            return;
        }

        if ($view->hasTemplate() )
        {
            $response = $args->getSubject()->Response();
            $view = $args->getSubject()->View();
            if($response->isException()) {
                return;
            }

            $view->addTemplateDir($this->Path() . 'Views/');
            $view->extendsTemplate('backend/bepado/menu_entry.tpl');
        }
    }
}