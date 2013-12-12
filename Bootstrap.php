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
        return '1.2.9';
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

	 	return array('success' => true, 'invalidateCache' => array('backend', 'config'));
	}

    /**
     * Registers the bepado events. As we register all events on the fly, only the early
     * Enlight_Controller_Front_StartDispatch-Event is needed.
     */
    public function createMyEvents()
    {
        $this->subscribeEvent(
            'Enlight_Controller_Front_DispatchLoopStartup',
            'onStartDispatch'
        );
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

        $modelManager->addAttribute(
           's_articles_attributes',
           'bepado', 'free_delivery',
           'int(1)'
        );

        $modelManager->addAttribute(
           's_articles_attributes',
           'bepado', 'update_price',
            'varchar(255)',
            true,
            'inherit'
        );

        $modelManager->addAttribute(
           's_articles_attributes',
           'bepado', 'update_image',
            'varchar(255)',
            true,
            'inherit'
        );

        $modelManager->addAttribute(
           's_articles_attributes',
           'bepado', 'update_long_description',
            'varchar(255)',
            true,
            'inherit'
        );

        $modelManager->addAttribute(
           's_articles_attributes',
           'bepado', 'update_short_description',
            'varchar(255)',
            true,
            'inherit'
        );

        $modelManager->addAttribute(
           's_articles_attributes',
           'bepado', 'update_name',
            'varchar(255)',
            true,
            'inherit'
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
            'value' => true,
            'helpText' => 'Für Anbieter von Produkten: Export diese automatisch nach bepado, wenn die Produkte geändert werden.'
        ));
        $form->setElement('text', 'bepadoDebugHost', array(
                'label' => 'Alternativer bepado Host (nur für Testzwecke)',
                'minLength' => 11
            )
        );

        $form->setElement('boolean', 'overwriteProductName', array(
            'label' => 'Beim Import Produkt-Namen überschreiben',
            'value' => true,
            'helpText' => 'Wenn Sie dieses Feld in der Regel selbst pflegen, wählen sie hier »Nein« aus. Sie können auf Artikel-Ebene Ausnahmen verwalten.'
        ));
        $form->setElement('boolean', 'overwriteProductPrice', array(
            'label' => 'Beim Import Produkt-Preise überschreiben',
            'value' => true,
            'helpText' => 'Wenn Sie dieses Feld in der Regel selbst pflegen, wählen sie hier »Nein« aus. Sie können auf Artikel-Ebene Ausnahmen verwalten.'
        ));
        $form->setElement('boolean', 'overwriteProductImage', array(
            'label' => 'Beim Import Produkt-Bilder überschreiben',
            'value' => true,
            'helpText' => 'Wenn Sie dieses Feld in der Regel selbst pflegen, wählen sie hier »Nein« aus. Sie können auf Artikel-Ebene Ausnahmen verwalten.'
        ));
        $form->setElement('boolean', 'overwriteProductShortDescription', array(
            'label' => 'Beim Import Produkt-Kurzbeschreibungen überschreiben',
            'value' => true,
            'helpText' => 'Wenn Sie dieses Feld in der Regel selbst pflegen, wählen sie hier »Nein« aus. Sie können auf Artikel-Ebene Ausnahmen verwalten.'
        ));
        $form->setElement('boolean', 'overwriteProductLongDescription', array(
            'label' => 'Beim Import Produkt-Langbeschreibungen überschreiben',
            'value' => true,
            'helpText' => 'Wenn Sie dieses Feld in der Regel selbst pflegen, wählen sie hier »Nein« aus. Sie können auf Artikel-Ebene Ausnahmen verwalten.'
        ));
    }

    /**
     * Will dynamically register all needed events
     *
     * @param Enlight_Event_EventArgs $args
     */
    public function onStartDispatch(Enlight_Event_EventArgs $args)
    {
        $this->registerMyLibrary();

        // Subscribe to the InitResource_BepadoSDK event first, some of the Subscribers might
        // need it as dependency
        $handler = new \Enlight_Event_Handler_Default(
            'Enlight_Bootstrap_InitResource_BepadoSDK',
            array($this, 'onInitResourceSDK'),
            0
        );
        $this->Application()->Events()->registerListener($handler);

        /**
         * Here we subscribe to the needed events and hooks.
         * Have a look at the getEvents() method defined in each subscriber class
         */
        $subscribers = array(
            new \Shopware\Bepado\Subscribers\ControllerPath(),
            new \Shopware\Bepado\Subscribers\TemplateExtension(),
            new \Shopware\Bepado\Subscribers\Checkout(),
            new \Shopware\Bepado\Subscribers\Voucher(),
            new \Shopware\Bepado\Subscribers\BasketWidget()
        );

        if ($this->Config()->get('autoUpdateProducts', true)) {
            $subscribers[] = new \Shopware\Bepado\Subscribers\Lifecycle();
        }


        /** @var $subscriber Shopware\Bepado\Subscribers\BaseSubscriber */
        foreach ($subscribers as $subscriber) {
            $subscriber->setBootstrap($this);
            $this->Application()->Events()->registerSubscriber($subscriber);
        }
    }

    public function onInitResourceSDK()
    {
        $this->registerMyLibrary();

        return $this->getBepadoFactory()->createSDK();
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
        // Removing the attributes will delete all mappings, product references to from-shops etc.
        // Currently it does not seem to be a good choice to have this enabled

//         $this->removeMyAttributes();
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

            $modelManager->removeAttribute(
               's_articles_attributes',
               'bepado', 'purchase_price'
            );

            $modelManager->removeAttribute(
               's_articles_attributes',
               'bepado', 'fixed_price'
            );

            $modelManager->removeAttribute(
               's_articles_attributes',
               'bepado', 'free_delivery'
            );


            $modelManager->removeAttribute(
               's_articles_attributes',
               'bepado', 'update_price'
            );

            $modelManager->removeAttribute(
               's_articles_attributes',
               'bepado', 'update_image'
            );

            $modelManager->removeAttribute(
               's_articles_attributes',
               'bepado', 'update_long_description'
            );

            $modelManager->removeAttribute(
               's_articles_attributes',
               'bepado', 'update_short_description'
            );

            $modelManager->removeAttribute(
               's_articles_attributes',
               'bepado', 'update_name'
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
     * Register additional namespaces for the libraries
     */
    public function registerMyLibrary()
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
     * @return Bepado\SDK\SDK
     */
    public function getSDK()
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

    public function getBasketHelper()
    {
        return $this->getBepadoFactory()->getBasketHelper();
    }


}
