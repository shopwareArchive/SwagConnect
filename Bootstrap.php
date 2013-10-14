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
    /** @var \Shopware\Bepado\BepadoFactory  */
    private $bepadoFactory;

    /**
     * Returns the current version of the plugin.
     *
     * @return string
     */
    public function getVersion()
    {
        return '1.2.2';
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

    /**
     * Creates product, order and category attributes
     */
    private function createMyAttributes()
    {
        /** @var Shopware\Components\Model\ModelManager $modelManager */
        $modelManager = $this->Application()->Models();
        
        $modelManager->addAttribute(
            's_articles_attributes',
            'bepado', 'shop_id',
            'varchar(255)'
        );
        $modelManager->addAttribute(
            's_articles_attributes',
            'bepado', 'source_id',
            'varchar(255)'
        );
        $modelManager->addAttribute(
            's_articles_attributes',
            'bepado', 'export_status',
            'text'
        );
        $modelManager->addAttribute(
            's_articles_attributes',
            'bepado', 'export_message',
            'text'
        );

        $modelManager->addAttribute(
            's_order_attributes',
            'bepado', 'shop_id',
            'int(11)'
        );
        $modelManager->addAttribute(
            's_order_attributes',
            'bepado', 'order_id',
            'int(11)'
        );

        $modelManager->addAttribute(
            's_articles_attributes',
            'bepado', 'categories',
            'text'
        );

        $modelManager->addAttribute(
           's_categories_attributes',
           'bepado', 'mapping',
           'text'
        );

        $modelManager->addAttribute(
           's_categories_attributes',
           'bepado', 'imported',
           'text'
        );

        $modelManager->addAttribute(
           's_articles_attributes',
           'bepado', 'purchase_price',
           'double'
        );

        $modelManager->addAttribute(
           's_articles_attributes',
           'bepado', 'fixed_price',
           'int(1)'
        );

        $modelManager->generateAttributeModels(array(
            's_articles_attributes',
            's_categories_attributes',
            's_order_details_attributes',
            's_order_basket_attributes',
        ));
    }

    /**
     * Creates the plugin menu item
     */
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

    /**
     * Creates the plugin configuration form
     */
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
                        Ext.get(apiField.inputEl).setStyle('background-color', response.success ? '#C7F5AA' : '#FFB0AD');
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
            'store' => 'base.CustomerGroup',
            'value' => 1
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
            'label' => 'Cloud-Search aktivieren',
            'value' => false
        ));
        $form->setElement('text', 'productDescriptionField', array(
            'label' => 'Feld für Produktbeschreibungen'
        ));
        $form->setElement('boolean', 'autoUpdateProducts', array(
            'label' => 'Geänderte Produkte automatisch mit bepado synchronisieren',
            'value' => true
        ));
        $form->setElement('text', 'bepadoDebugHost', array(
                'label' => 'Alternativer bepado Host (nur für Testzwecke)',
                'minLength' => 11
            )
        );
    }

    /**
     * Register the plugin events
     */
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
            'Enlight_Controller_Action_PostDispatch_Backend_Article',
            'onPostDispatchBackendArticle'
        );

        $this->subscribeEvent(
            'Enlight_Controller_Action_PostDispatch_Backend_Order',
            'onPostDispatchBackendOrder'
        );

	    $this->subscribeEvent(
		    'Enlight_Controller_Action_PostDispatch_Backend_Index',
		    'onPostDispatch'
	    );

        $this->subscribeEvent('Shopware\Models\Article\Article::postPersist', 'onUpdateArticle');
        $this->subscribeEvent('Shopware\Models\Article\Article::postUpdate', 'onUpdateArticle');


    }

    /**
     * Create necessary tables
     */
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
//        $this->removeMyAttributes();
        return true;
	}

    /**
     * @return bool
     */
    public function update()
    {
        $this->createMyEvents();
        $this->createMyForm();
        $this->createMyMenu();
        return true;
    }

    /**
     * Remove the attributes when uninstalling the plugin
     */
    private function removeMyAttributes()
    {
        /** @var Shopware\Components\Model\ModelManager $modelManager */
        $modelManager = $this->Application()->Models();


        try {
            $modelManager->removeAttribute(
                's_articles_attributes',
                'bepado', 'shop_id'
            );
            $modelManager->removeAttribute(
                's_articles_attributes',
                'bepado', 'source_id'
            );
            $modelManager->removeAttribute(
                's_articles_attributes',
                'bepado', 'export_status'
            );
            $modelManager->removeAttribute(
                's_articles_attributes',
                'bepado', 'export_message'
            );

            $modelManager->removeAttribute(
                's_order_attributes',
                'bepado', 'shop_id'
            );
            $modelManager->removeAttribute(
                's_order_attributes',
                'bepado', 'order_id'
            );

            $modelManager->removeAttribute(
                's_articles_attributes',
                'bepado', 'categories'
            );

            $modelManager->removeAttribute(
                's_categories_attributes',
                'bepado', 'mapping'
            );

            $modelManager->removeAttribute(
                's_categories_attributes',
                'bepado', 'imported'
            );

            $modelManager->generateAttributeModels(array(
                's_articles_attributes',
                's_categories_attributes',
                's_order_details_attributes',
                's_order_basket_attributes',
            ));
        } catch(Exception $e) { }

    }

    /**
     * Register the template directory of the plugin
     */
    private function registerMyTemplateDir()
    {
        $this->Application()->Template()->addTemplateDir(
            $this->Path() . 'Views/', 'bepado'
        );
    }

    /**
     * Register additional namespaces for the libraries
     */
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

    /**
     * Register the snippet folder
     */
    private function registerMySnippets()
    {
        $this->Application()->Snippets()->addConfigDir(
            $this->Path() . 'Snippets/'
        );
    }

    /**
     * Lazy getter for the bepadoFactory
     *
     * @return \Shopware\Bepado\BepadoFactory
     */
    public function getBepadoFactory()
    {
        $this->registerMyLibrary();

        if (!$this->bepadoFactory) {
            $this->bepadoFactory = new \Shopware\Bepado\BepadoFactory();
        }

        return $this->bepadoFactory;
    }

    /**
     * @param   Enlight_Event_EventArgs $args
     * @return  Bepado\SDK\SDK
     */
    public function onInitResourceSDK(Enlight_Event_EventArgs $args)
    {
        return $this->getBepadoFactory()->createSDK();
    }

    /**
     * @return Bepado\SDK\SDK
     */
    private function getSDK()
    {
        return $this->getBepadoFactory()->getSDK();
    }

    /**
     * @return \Shopware\Bepado\Helper
     */
    public function getHelper()
    {
        return $this->getBepadoFactory()->getHelper();
    }

    /**
     * Callback method to update changed bepado products
     *
     * @param Enlight_Event_EventArgs $eventArgs
     */
    public function onUpdateArticle(Enlight_Event_EventArgs $eventArgs)
    {
        if (!$this->Config()->get('autoUpdateProducts', true)) {
            return;
        }

        $entity = $eventArgs->get('entity');
        $id = $entity->getId();

        $model = $this->getHelper()->getArticleModelById($id);

        // Check if we have a valid model
        if (!$model || !$model->getAttribute()) {
            return;
        }

        // Check if entity is a bepado product
        $status = $model->getAttribute()->getBepadoExportStatus();
        if (empty($status)) {
            return;
        }

        // Mark the product for bepado update
        $this->getHelper()->insertOrUpdateProduct(array($id));
    }

    /**
     * Register the bepado backend controller
     *
     * @param   Enlight_Event_EventArgs $args
     * @return  string
     * @Enlight\Event Enlight_Controller_Dispatcher_ControllerPath_Backend_Bepado
     */
    public function onGetControllerPathBackend(Enlight_Event_EventArgs $args)
    {
        $this->registerMyTemplateDir();
        $this->registerMySnippets();
        return $this->Path() . 'Controllers/Backend/Bepado.php';
    }

    /**
     * Register the bepadoGateway backend controller
     *
     * @param   Enlight_Event_EventArgs $args
     * @return  string
     */
    public function onGetControllerPathGateway(Enlight_Event_EventArgs $args)
    {
        return $this->Path() . 'Controllers/Backend/BepadoGateway.php';
    }

    /**
     * Register the bepado frontend controller
     *
     * @param   Enlight_Event_EventArgs $args
     * @return  string
     */
    public function onGetControllerPathFrontend(Enlight_Event_EventArgs $args)
    {
        $this->registerMyTemplateDir();
        return $this->Path() . 'Controllers/Frontend/Bepado.php';
    }

    /**
     * Helper method to create an address struct from shopware session info
     *
     * @param $userData
     * @return \Bepado\SDK\Struct\Address
     */
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
     * Event listener method for the checkout confirm- and cartAction.
     *
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
        $basket['contentOrg'] = $basket['content'];
        foreach ($basket['content'] as $key => &$row) {
            if(!empty($row['mode'])) {
                continue;
            }
            $product = $helper->getProductById($row['articleID']);
            if($product === null || $product->shopId === null) {
                continue;
            }
	        $row['bepadoShopId'] = $product->shopId;
            $bepadoProducts[$product->shopId][$product->sourceId] = $product;
            $bepadoContent[$product->shopId][$product->sourceId] = $row;

            //if($actionName == 'cart') {
                unset($basket['content'][$key]);
            //}
        }
        $basket['content'] = array_values($basket['content']);
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

            //Remove original shipping costs
            $shippingCostsOrg = $basket['sShippingcosts'];
            $shippingCostsOrgNet = $basket['sShippingcostsNet'];
            $basket['sShippingcosts'] = 0;
            $basket['sShippingcostsWithTax'] = 0;
            $basket['sShippingcostsNet'] = 0;
            $basket['AmountNumeric'] -= $shippingCostsOrg;
            $basket['AmountNetNumeric'] -= $shippingCostsOrgNet;
            $basket['sAmount'] -= $shippingCostsOrg;
            $rate = number_format($basket['sShippingcostsTax'], 2, '.', '');
            $basket['sTaxRates'][$rate] -= $shippingCostsOrg - $shippingCostsOrgNet;
            if(!empty($basket['sAmountWithTax'])) {
                $basket['sAmountWithTax'] -= $shippingCostsOrg;
            }
        }

        $shippingCosts = array_sum($bepadoShippingCosts);
        $shippingCostsOrg = $basket['sShippingcosts'];
        $basket['sShippingcosts'] += $shippingCosts;
        $basket['sShippingcostsWithTax'] += $shippingCosts;
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
            $basket['content'] = $basket['contentOrg']; unset($basket['contentOrg']);
            $variables->exchangeArray(array_merge(
                $variables->getArrayCopy(), $newVariables, array('sBasket' => $basket)
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
     * Event listener method for the checkout->finishAction. Will reserve products and redirect to
     * the confirm page if a product cannot be reserved
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
     * Event listener method for the frontend detail page. Will add bepado template variables if the current product
     * is a bepado product.
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
     * Hooks the sSaveOrder frontend method ans reserves the bepado products
     *
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
     * Event listener method for frontend searches
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
     * Extends the product list in the backend in order to have a special hint for bepado products
     *
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
                $subject->View()->data = $this->markBepadoProducts(
                    $subject->View()->data
                );
                break;
            default:
                break;
        }
    }

    public function onPostDispatchBackendArticle(Enlight_Event_EventArgs $args)
    {
        /** @var $subject Enlight_Controller_Action */
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
     * Extends the order backend module in order to show a special hint for bepado products
     *
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
     * Callback method for the Backend/Index postDispatch event.
     * Will add the bepado sprite to the menu
     *
	 * @param Enlight_Event_EventArgs $args
	 * @returns boolean|void
	 */
	public function onPostDispatch(Enlight_Event_EventArgs $args)
    {
        /** @var $action Enlight_Controller_Action */
        $action = $args->getSubject();
        $request = $action->Request();
        $response = $action->Response();
        $view = $action->View();

        if (!$request->isDispatched()
            || $response->isException()
            || !$view->hasTemplate()
        ) {
            return;
        }

        $view->addTemplateDir($this->Path() . 'Views/');
        $view->extendsTemplate('backend/bepado/menu_entry.tpl');
    }
}
