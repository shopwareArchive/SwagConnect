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
use Shopware\CustomModels\Bepado\Config;

/**
 * @category  Shopware
 * @package   Shopware\Plugins\SwagBepado
 */
final class Shopware_Plugins_Backend_SwagBepado_Bootstrap extends Shopware_Components_Plugin_Bootstrap
{
    /** @var \Shopware\Bepado\Components\BepadoFactory */
    private $bepadoFactory;

    /**
     * Returns the current version of the plugin.
     *
     * @return string
     */
    public function getVersion()
    {
        return '1.4.21';
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
            'description' => file_get_contents($this->Path() . 'info.txt'),
            'link' => 'http://www.shopware.de/',
        );
    }

    /**
     * Install plugin method
     *
     * @throws \RuntimeException
     * @return bool
     */
    public function install()
    {
        if (!$this->assertVersionGreaterThen('4.1.0')) {
            throw new \RuntimeException('Shopware version 4.1.0 or later is required.');
        };

        $this->createMyMenu();
        $this->createMyForm();
        $this->createMyEvents();

        $this->createMyTables();
        $this->createMyAttributes();
        $this->populateConfigTable();
        $this->importSnippets();

        $this->createEngineElement();

        // Populate the s_premium_dispatch_attributes table with attributes for all dispatches
        // so that all existing dispatches are immediately available for bepado
        Shopware()->Db()->exec('
            INSERT IGNORE INTO `s_premium_dispatch_attributes` (`dispatchID`)
            SELECT `id` FROM `s_premium_dispatch`
        ');

        return array('success' => true, 'invalidateCache' => array('backend', 'config'));
    }

    /**
     * @return bool
     */
    public function update($version)
    {
        // Remove old productDescriptionField
        // removeElement does seem to have some issued, so using plain SQL here
        if (version_compare($version, '1.2.59', '<=')) {
            $id = $this->Form()->getId();

            Shopware()->Db()->query('DELETE FROM s_core_config_elements WHERE form_id = ? AND name = ?', array(
                $id, 'productDescriptionField'
            ));
        }

        $this->createMyMenu();
        $this->createMyForm();
        $this->createMyEvents();

        $this->createMyTables();
        $this->createMyAttributes();
        $this->importSnippets();

        $this->createEngineElement();

        // When the dummy plugin is going to be installed, don't do the later updates
        if (version_compare($version, '0.0.1', '<=')) {
            return true;
        }

        // Force an SDK re-verify
        Shopware()->Db()->query('
            UPDATE bepado_shop_config
            SET s_config = ?
            WHERE s_shop = "_last_update_"
            LIMIT 1; ',
            array(time() - 8 * 60 * 60 * 24)
        );

        // Migrate old attributes to bepado attributes
        if (version_compare($version, '1.2.18', '<=')) {
            // Copy s_articles_attributes to own attribute table
            $sql = 'INSERT IGNORE INTO `s_plugin_bepado_items`
              (`article_id`, `article_detail_id`, `shop_id`, `source_id`, `export_status`, `export_message`, `categories`,
              `purchase_price`, `fixed_price`, `free_delivery`, `update_price`, `update_image`,
              `update_long_description`, `update_short_description`, `update_name`, `last_update`,
              `last_update_flag`)
            SELECT `articleID`, `articledetailsID`, `bepado_shop_id`, `bepado_source_id`, `bepado_export_status`,
            `bepado_export_message`, `bepado_categories`, `bepado_purchase_price`, `bepado_fixed_price`,
            `bepado_free_delivery`, `bepado_update_price`, `bepado_update_image`, `bepado_update_long_description`,
             `bepado_update_short_description`, `bepado_update_name`, `bepado_last_update`, `bepado_last_update_flag`
            FROM `s_articles_attributes`';
            Shopware()->Db()->exec($sql);

            $this->removeMyAttributes();
        }

        if (version_compare($version, '1.2.70', '<=')) {
            Shopware()->Db()->exec('ALTER TABLE `bepado_shop_config` CHANGE `s_config` `s_config` LONGBLOB NOT NULL;');
        }

        // Split category mapping into mapping for import and export
        if (version_compare($version, '1.4.8', '<=')) {
            Shopware()->Models()->removeAttribute(
                's_categories_attributes',
                'bepado', 'mapping'
            );
            Shopware()->Models()->generateAttributeModels(array(
                's_categories_attributes'
            ));
        }

        // A product does only have one bepado category mapped
        if (version_compare($version, '1.4.11', '<=')) {
            try {
                $sql = 'ALTER TABLE `s_plugin_bepado_items` change `categories` `category` text;';
                Shopware()->Db()->exec($sql);
            } catch (\Exception $e) {
                // if table was already altered, ignore
            }

            // Get serialized categories -.-
            $sql = 'SELECT id, category FROM `s_plugin_bepado_items` WHERE `category` LIKE "%{%" OR `category` = "N;"';
            $rows = Shopware()->Db()->fetchAll($sql);

            // Build values array with unserialized categories
            $values = array();
            foreach ($rows as $row) {
                $category = unserialize($row['category']);
                if (!empty($category) && is_array($category)) {
                    $category = array_pop($category);
                } else {
                    $category = null;
                }
                $values[$row['id']] = $category;
            }

            // Update the category one by one. This is not optimal, but only affects a few beta testers
            Shopware()->Db()->beginTransaction();
            foreach ($values as $id => $value) {
                Shopware()->Db()->query('UPDATE `s_plugin_bepado_items` SET `category` = ? WHERE id = ? ',
                array(
                    $category,
                    $id
                ));
            }
            Shopware()->Db()->commit();
        }

        // Make the bepado price nullable in order to prevent issues with variant generation
        if (version_compare($version, '1.4.17', '<=')) {
            try {
                $sql = 'ALTER TABLE `s_articles_prices_attributes` MODIFY COLUMN `bepado_price` DOUBLE DEFAULT 0 NULL;';
                Shopware()->Db()->exec($sql);
            } catch (\Exception $e) {
            }
        }

        return true;
    }

    /**
     * Creates an engine element so that the bepadoProductDescription is displayed in the article
     */
    public function createEngineElement()
    {
        $repo = Shopware()->Models()->getRepository('Shopware\Models\Article\Element');
        $element = $repo->findOneBy(array('name' => 'bepadoProductDescription'));

        if (!$element) {
            $element = new \Shopware\Models\Article\Element();
            $element->setName('bepadoProductDescription');
            $element->setType('html');
            $element->setLabel('bepado Beschreibung');
            $element->setTranslatable(1);
            $element->setHelp('Falls Sie die Langbeschreibung ihres Artikels in diesem Attribut-Feld pflegen, wird statt der Langbeschreibung der Inhalt dieses Feldes exportiert');

            Shopware()->Models()->persist($element);
            Shopware()->Models()->flush();
        }
    }

    public function importSnippets()
    {
        $sql = file_get_contents($this->Path() . 'Snippets/frontend.sql');
        Shopware()->Db()->exec($sql);
    }

    /**
     * Creates the default config
     */
    public function populateConfigTable()
    {
        $this->registerCustomModels();

        /** @var Shopware\CustomModels\Bepado\ConfigRepository $repo */
        $repo = $this->Application()->Models()->getRepository('Shopware\CustomModels\Bepado\Config');


        $repo->setConfig('priceGroupForPriceExport', 'EK');
        $repo->setConfig('priceGroupForPurchasePriceExport', 'EK');
        $repo->setConfig('priceFieldForPriceExport', 'price');
        $repo->setConfig('priceFieldForPurchasePriceExport', 'basePrice');

        Shopware()->Models()->flush();

    }

    /**
     * Registers the bepado events. As we register all events on the fly, only the early
     * Enlight_Controller_Front_StartDispatch-Event is needed.
     */
    public function createMyEvents()
    {
        $this->subscribeEvent(
            'Enlight_Bootstrap_InitResource_BepadoSDK',
            'onInitResourceSDK'
        );

        $this->subscribeEvent(
            'Enlight_Controller_Front_DispatchLoopStartup',
            'onStartDispatch'
        );

        Shopware()->Db()->query(
            'DELETE FROM s_crontab WHERE `name` = :name AND `action` = :action',
            array('name' => 'SwagBepado', 'action' => 'importImages')
        );
        $this->createCronJob(
            'SwagBepado',
            'importImages',
            60 * 30,
            true
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
            's_categories_attributes',
            'bepado', 'import_mapping',
            'text'
        );

        $modelManager->addAttribute(
            's_categories_attributes',
            'bepado', 'export_mapping',
            'text'
        );

        $modelManager->addAttribute(
            's_categories_attributes',
            'bepado', 'imported',
            'text'
        );

        $modelManager->addAttribute(
            's_media_attributes',
            'bepado', 'hash',
            'varchar(255)'
        );

        $modelManager->addAttribute(
            's_premium_dispatch_attributes',
            'bepado', 'allowed',
            'int(1)',
            false,
            1
        );

        $modelManager->addAttribute(
            's_articles_attributes',
            'bepado', 'product_description',
            'text'
        );

        $modelManager->addAttribute(
            's_articles_prices_attributes',
            'bepado', 'price',
            'double',
            true,
            0
        );

        $modelManager->generateAttributeModels(array(
            's_articles_attributes',
            's_order_attributes',
            's_articles_prices_attributes',
            's_premium_dispatch_attributes',
            's_categories_attributes',
            's_order_details_attributes',
            's_order_basket_attributes',
            's_media_attributes'
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
        $form->setElement('select', 'alternateDescriptionField',
            array(
                'required' => true,
                'editable' => false,
                'value' => 'a.descriptionLong',
                'label' => 'Produkt Beschreibungsfeld',
                'helpText' => 'Wenn Sie für bepado-Produkte nicht den Standard-Artikel-Langtext nutzen möchten, können Sie hier ein alternatives Feld definieren.',
                'store' => array(
                    array('attribute.bepadoProductDescription', 'attribute.bepadoProductDescription'),
                    array('a.description', 'Artikel-Kurzbeschreibung'),
                    array('a.descriptionLong', 'Artikel-Langbeschreibung')
                )
            )
        );

        $form->setElement('select', 'bepadoAttribute',
            array(
                'required' => true,
                'editable' => false,
                'value' => 19,
                'label' => 'bepado Attribut',
                'helpText' => 'In das gewählte Attribut wird die Quell-ID des jeweiligen bepado-Artikels gespeichert. So können Sie bspw. im RisikoManagment leicht bepado-Artikel identifizieren.',
                'store' => array(
                    array(1, 'attr1'),
                    array(2, 'attr2'),
                    array(3, 'attr3'),
                    array(4, 'attr4'),
                    array(5, 'attr5'),
                    array(6, 'attr6'),
                    array(7, 'attr7'),
                    array(8, 'attr8'),
                    array(9, 'attr9'),
                    array(10, 'attr10'),
                    array(11, 'attr11'),
                    array(12, 'attr12'),
                    array(13, 'attr13'),
                    array(14, 'attr14'),
                    array(15, 'attr15'),
                    array(16, 'attr16'),
                    array(17, 'attr17'),
                    array(18, 'attr18'),
                    array(19, 'attr19'),
                    array(20, 'attr20'),
                )
            )
        );

        $form->setElement('select', 'importImagesOnFirstImport',
            array(
                'required' => true,
                'editable' => false,
                'value' => 0,
                'label' => 'Bilder beim Produkt-Erstimport laden',
                'helpText' => 'Das Importieren von Bildern beim Erstimport der Produkte kann den Import extrem verlängern. Empfohlen ist die Verwendung des Shopware-Cronjobs um die Bilder nachgelagert zu importieren. Auch über »Geänderte Produkte« können die Bilder nachgelagert importiert werden',
                'store' => array(
                    array(0, 'Nein (No)'),
                    array(1, 'Ja (Yes)'),
                )
            )
        );

        $form->setElement('boolean', 'autoUpdateProducts', array(
            'label' => 'Geänderte Produkte automatisch mit bepado synchronisieren',
            'value' => true,
            'helpText' => 'Für Anbieter von Produkten: Export diese automatisch nach bepado, wenn die Produkte geändert werden.'
        ));

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

        $form->setElement('text', 'bepadoDebugHost', array(
                'label' => 'Alternativer bepado Host (nur für Testzwecke)',
                'minLength' => 11
            )
        );
        $form->setElement('boolean', 'logRequest', array(
                'label' => 'Anfragen des bepado-Servers mitschreiben',
                'value' => false,
                'helpText' => 'Schreibt alle Anfragen von bepado.de und die Antwort des bepado-Plugins mit. Hierbei können schnell viele Daten anfallen.'
            )
        );
    }

    /**
     * Will dynamically register all needed events
     *
     * @param Enlight_Event_EventArgs $args
     */
    public function onStartDispatch(Enlight_Event_EventArgs $args)
    {
        $this->registerMyLibrary();

        /** @var Shopware\CustomModels\Bepado\ConfigRepository $repo */
        $repo = $this->Application()->Models()->getRepository('Shopware\CustomModels\Bepado\Config');
        $verified = $repo->getConfig('apiKeyVerified', false);

        $subscribers = $this->getDefaultSubscribers();

        // Some subscribers may only be used, if the SDK is verified
        if ($verified) {
            $subscribers = array_merge($subscribers, $this->getSubscribersForVerifiedKeys());
        // These subscribers are used if the api key is not valid
        } else {
            $subscribers = array_merge($subscribers, $this->getSubscribersForUnverifiedKeys());
        }

        /** @var $subscriber Shopware\Bepado\Subscribers\BaseSubscriber */
        foreach ($subscribers as $subscriber) {
            $subscriber->setBootstrap($this);
            $this->Application()->Events()->registerSubscriber($subscriber);
        }
    }

    public function getSubscribersForUnverifiedKeys()
    {
        return array(
            new \Shopware\Bepado\Subscribers\DisableBepadoInFrontend()
        );
    }

    /**
     * These subscribers will only be used, once the user has verified his api key
     * This will prevent the users from having bepado extensions in their frontend
     * even if they cannot use bepado due to the missing / wrong api key
     *
     * @return array
     */
    public function getSubscribersForVerifiedKeys()
    {
        $subscribers = array(
            new \Shopware\Bepado\Subscribers\TemplateExtension(),
            new \Shopware\Bepado\Subscribers\Checkout(),
            new \Shopware\Bepado\Subscribers\Voucher(),
            new \Shopware\Bepado\Subscribers\BasketWidget(),
            new \Shopware\Bepado\Subscribers\Dispatches(),
        );


        if ($this->Config()->get('autoUpdateProducts', true)) {
            $subscribers[] = new \Shopware\Bepado\Subscribers\Lifecycle();
        }

        return $subscribers;
    }

    /**
     * Default subscribers can safely be used, even if the api key wasn't verified, yet
     *
     * @return array
     */
    public function getDefaultSubscribers()
    {
        return array(
            new \Shopware\Bepado\Subscribers\OrderDocument(),
            new \Shopware\Bepado\Subscribers\ControllerPath(),
            new \Shopware\Bepado\Subscribers\CronJob(),
            new \Shopware\Bepado\Subscribers\ArticleList(),
            new \Shopware\Bepado\Subscribers\Article(),
            new \Shopware\Bepado\Subscribers\Bepado(),
        );
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;", "
            CREATE TABLE IF NOT EXISTS `bepado_shop_config` (
              `s_shop` varchar(32) NOT NULL,
              `s_config` LONGBLOB NOT NULL,
              `changed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`s_shop`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;", "
            CREATE TABLE IF NOT EXISTS `s_plugin_bepado_config` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `name` varchar(255) NOT NULL,
              `value` varchar(255) NOT NULL,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;", "
            CREATE TABLE IF NOT EXISTS `bepado_shipping_costs` (
              `sc_from_shop` VARCHAR(32) NOT NULL,
              `sc_to_shop` VARCHAR(32) NOT NULL,
              `sc_revision` VARCHAR(32) NOT NULL,
              `sc_shipping_costs` LONGBLOB NOT NULL,
              `changed` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`sc_from_shop`, `sc_to_shop`),
              INDEX (`sc_revision`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;", "
            CREATE TABLE IF NOT EXISTS `s_plugin_bepado_log` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `isError` int(1) NOT NULL,
              `service` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
              `command` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
              `request` text COLLATE utf8_unicode_ci DEFAULT NULL,
              `response` text COLLATE utf8_unicode_ci DEFAULT NULL,
              `time` datetime NOT NULL,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;", "
            CREATE TABLE IF NOT EXISTS `s_plugin_bepado_items` (
             `id` int(11) NOT NULL AUTO_INCREMENT,
             `article_id` int(11) unsigned DEFAULT NULL,
             `article_detail_id` int(11) unsigned DEFAULT NULL,
             `shop_id` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
             `source_id` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
             `export_status` text COLLATE utf8_unicode_ci,
             `export_message` text COLLATE utf8_unicode_ci,
             `category` text COLLATE utf8_unicode_ci,
             `purchase_price` double DEFAULT NULL,
             `fixed_price` int(1) DEFAULT NULL,
             `free_delivery` int(1) DEFAULT NULL,
             `update_price` varchar(255) COLLATE utf8_unicode_ci DEFAULT 'inherit',
             `update_image` varchar(255) COLLATE utf8_unicode_ci DEFAULT 'inherit',
             `update_long_description` varchar(255) COLLATE utf8_unicode_ci DEFAULT 'inherit',
             `update_short_description` varchar(255) COLLATE utf8_unicode_ci DEFAULT 'inherit',
             `update_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT 'inherit',
             `last_update` longtext COLLATE utf8_unicode_ci,
             `last_update_flag` int(11) DEFAULT NULL,
             PRIMARY KEY (`id`),
             UNIQUE KEY `article_detail_id` (`article_detail_id`),
             KEY `article_id` (`article_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
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
        // $this->removeMyAttributes();

        // deactive all bepado products on uninstall
        $sql = '
        UPDATE s_articles
        INNER JOIN s_plugin_bepado_items
          ON s_plugin_bepado_items.article_id = s_articles.id
          AND shop_id IS NOT NULL
        SET s_articles.active = false
        ';
        Shopware()->Db()->exec($sql);

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

//            $modelManager->removeAttribute(
//                's_order_attributes',
//                'bepado', 'shop_id'
//            );
//            $modelManager->removeAttribute(
//                's_order_attributes',
//                'bepado', 'order_id'
//            );

            $modelManager->removeAttribute(
                's_articles_attributes',
                'bepado', 'categories'
            );

//            $modelManager->removeAttribute(
//                's_categories_attributes',
//                'bepado', 'mapping'
//            );
//
//            $modelManager->removeAttribute(
//                's_categories_attributes',
//                'bepado', 'imported'
//            );

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

            $modelManager->removeAttribute(
                's_articles_attributes',
                'bepado', 'last_update'
            );

            $modelManager->removeAttribute(
                's_articles_attributes',
                'bepado', 'last_update_flag'
            );

//            $modelManager->removeAttribute(
//                's_premium_dispatch_attributes',
//                'bepado', 'allowed'
//            );

//            $modelManager->removeAttribute(
//                's_media_attributes',
//                'bepado', 'hash'
//            );

            $modelManager->generateAttributeModels(array(
                's_articles_attributes',
//                's_premium_dispatch_attributes',
//                's_categories_attributes',
//                's_order_details_attributes',
//                's_order_basket_attributes',
//                's_media_attributes'
            ));
        } catch (Exception $e) {
        }

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
            $this->Path()
        );

        $this->registerCustomModels();
    }

    /**
     * Lazy getter for the bepadoFactory
     *
     * @return \Shopware\Bepado\Components\BepadoFactory
     */
    public function getBepadoFactory()
    {
        $this->registerMyLibrary();

        if (!$this->bepadoFactory) {
            $this->bepadoFactory = new \Shopware\Bepado\Components\BepadoFactory($this->getVersion());
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
     * @return \Shopware\Bepado\Components\Helper
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
