<?php

namespace ShopwarePlugins\Connect\Bootstrap;

use Shopware\Models\Article\Element;
use Shopware\Models\Customer\Group;

/**
 * The setup class does the basic setup of the shopware Connect plugin. All operations should be implemented in a way
 * that they can also be run on update of the plugin
 *
 * Class Setup
 * @package ShopwarePlugins\Connect\Bootstrap
 */
class Setup
{
    protected $bootstrap;

    public function __construct(\Shopware_Plugins_Backend_SwagConnect_Bootstrap $bootstrap)
    {
        $this->bootstrap = $bootstrap;
    }

    public function run($fullSetup)
    {
        $this->createMyEvents();
        $this->createMyTables();
        $this->createMyAttributes();
        $this->populateConfigTable();
        $this->importSnippets();
        $this->generateConnectPaymentAttribute();
        $this->populateDispatchAttributes();
        $this->populateConnectPaymentAttribute();


        $this->createConnectCustomerGroup();


        if ($fullSetup) {
            $this->createMyMenu();
            $this->createEngineElement();
            $this->populatePaymentStates();
        }
    }

    /**
     * Creates the plugin menu item
     */
    private function createMyMenu()
    {
        $connectItem = $this->bootstrap->Menu()->findOneBy(array('label' => 'Connect'));
        // check if shopware Connect menu item exists
        if (!$connectItem) {
            $models = Shopware()->Models();
            $configComponent = new \ShopwarePlugins\Connect\Components\Config($models);

            //move help menu item after Connect
            $helpItem = $this->bootstrap->Menu()->findOneBy(array('label' => ''));
            $helpItem->setPosition(1);
            Shopware()->Models()->persist($helpItem);
            Shopware()->Models()->flush();

            $parent = $this->bootstrap->createMenuItem(array(
                'label' => 'Connect',
                'controller' => 'Connect',
                'class' => 'connect-icon',
                'active' => 1,
            ));

            if ($configComponent->getConfig('apiKey', '') == '') {
                $this->bootstrap->createMenuItem(array(
                    'label' => 'Register',
                    'controller' => 'Connect',
                    'action' => 'Register',
                    'class' => 'contents--media-manager',
                    'active' => 1,
                    'parent' => $parent
                ));
            }

            $this->bootstrap->createMenuItem(array(
                'label' => 'Import',
                'controller' => 'Connect',
                'action' => 'Import',
                'class' => 'contents--import-export',
                'active' => 1,
                'parent' => $parent
            ));

            $this->bootstrap->createMenuItem(array(
                'label' => 'Export',
                'controller' => 'Connect',
                'action' => 'Export',
                'class' => 'contents--import-export',
                'active' => 1,
                'parent' => $parent
            ));

            $this->bootstrap->createMenuItem(array(
                'label' => 'Settings',
                'controller' => 'Connect',
                'action' => 'Settings',
                'class' => 'sprite-gear',
                'active' => 1,
                'parent' => $parent
            ));

            $sql = "INSERT IGNORE INTO `s_core_snippets` (`namespace`, `shopID`, `localeID`, `name`, `value`, `created`, `updated`) VALUES
            ('backend/index/view/main', 1, 1, 'Connect', 'Connect', '2016-03-17 18:32:48', '2016-03-17 18:32:48'),
            ('backend/index/view/main', 1, 2, 'Connect', 'Connect', '2016-03-17 18:32:48', '2016-03-17 18:32:48'),
            ('backend/index/view/main', 1, 1, 'Connect/Export', 'Export', '2016-03-17 18:32:48', '2016-03-17 18:32:48'),
            ('backend/index/view/main', 1, 2, 'Connect/Export', 'Export', '2016-03-17 18:32:48', '2016-03-17 18:32:48'),
            ('backend/index/view/main', 1, 1, 'Connect/Settings', 'Einstellungen', '2016-03-17 18:32:48', '2016-03-17 18:32:48'),
            ('backend/index/view/main', 1, 2, 'Connect/Settings', 'Settings', '2016-03-17 18:32:48', '2016-03-17 18:32:48'),
            ('backend/index/view/main', 1, 1, 'Connect/Register', 'Einstieg', '2016-03-17 18:32:48', '2016-03-17 18:32:48'),
            ('backend/index/view/main', 1, 2, 'Connect/Register', 'Register', '2016-03-17 18:32:48', '2016-03-17 18:32:48'),
            ('backend/index/view/main', 1, 1, 'Connect/Import', 'Import', '2016-03-17 18:32:48', '2016-03-17 18:32:48'),
            ('backend/index/view/main', 1, 2, 'Connect/Import', 'Import', '2016-03-17 18:32:48', '2016-03-17 18:32:48')

            ON DUPLICATE KEY UPDATE
              `namespace` = VALUES(`namespace`),
              `shopID` = VALUES(`shopID`),
              `name` = VALUES(`name`),
              `localeID` = VALUES(`localeID`),
              `value` = VALUES(`value`)
              ;";
            Shopware()->Db()->exec($sql);
        }
    }

    /**
     * Registers the shopware Connect events. As we register all events on the fly, only the early
     * Enlight_Controller_Front_StartDispatch-Event is needed.
     */
    public function createMyEvents()
    {
        $this->bootstrap->subscribeEvent(
            'Enlight_Bootstrap_InitResource_ConnectSDK',
            'onInitResourceSDK'
        );

        $this->bootstrap->subscribeEvent(
            'Enlight_Controller_Front_DispatchLoopStartup',
            'onStartDispatch'
        );

        Shopware()->Db()->query(
            'DELETE FROM s_crontab WHERE `name` = :name AND `action` = :action',
            array('name' => 'SwagConnect', 'action' => 'importImages')
        );
        Shopware()->Db()->query(
            'DELETE FROM s_crontab WHERE `name` = :name AND `action` = :action',
            array('name' => 'SwagConnect', 'action' => 'ShopwareConnect_CronJob_ImportImages')
        );
        $this->bootstrap->createCronJob(
            'SwagConnect',
            'ShopwareConnect_CronJob_ImportImages',
            60 * 30,
            true
        );
    }


    /**
     * Create necessary tables
     */
    private function createMyTables()
    {
        $queries = array("
            CREATE TABLE IF NOT EXISTS `sw_connect_change` (
              `c_source_id` varchar(64) NOT NULL,
              `c_operation` char(8) NOT NULL,
              `c_revision` decimal(20,10) NOT NULL,
              `c_product` longblob,
              `changed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              UNIQUE KEY `c_revision` (`c_revision`),
              KEY `c_source_id` (`c_source_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;", "
           CREATE TABLE IF NOT EXISTS `sw_connect_data` (
              `d_key` varchar(32) NOT NULL,
              `d_value` varchar(256) NOT NULL,
              `changed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`d_key`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;", "
            CREATE TABLE IF NOT EXISTS `sw_connect_product` (
              `p_source_id` varchar(64) NOT NULL,
              `p_hash` varchar(64) NOT NULL,
              `changed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`p_source_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;", "
            CREATE TABLE IF NOT EXISTS `sw_connect_reservations` (
              `r_id` varchar(32) NOT NULL,
              `r_state` varchar(12) NOT NULL,
              `r_order` longblob NOT NULL,
              `changed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`r_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;", "
            CREATE TABLE IF NOT EXISTS `sw_connect_shop_config` (
              `s_shop` varchar(32) NOT NULL,
              `s_config` LONGBLOB NOT NULL,
              `changed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`s_shop`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;", "
            CREATE TABLE IF NOT EXISTS `s_plugin_connect_config` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `name` varchar(255) NOT NULL,
              `value` TEXT NOT NULL,
              `shopId` int(11) NULL,
              `groupName` varchar(255) NULL,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;", "
            CREATE TABLE IF NOT EXISTS `sw_connect_shipping_costs` (
              `sc_from_shop` VARCHAR(32) NOT NULL,
              `sc_to_shop` VARCHAR(32) NOT NULL,
              `sc_revision` VARCHAR(32) NOT NULL,
              `sc_shipping_costs` LONGBLOB NOT NULL,
              `sc_customer_costs` LONGBLOB NOT NULL,
              `changed` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`sc_from_shop`, `sc_to_shop`),
              INDEX (`sc_revision`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;", "
            CREATE TABLE IF NOT EXISTS `s_plugin_connect_log` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `isError` int(1) NOT NULL,
              `service` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
              `command` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
              `request` text COLLATE utf8_unicode_ci DEFAULT NULL,
              `response` text COLLATE utf8_unicode_ci DEFAULT NULL,
              `time` datetime NOT NULL,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;", "
            CREATE TABLE IF NOT EXISTS `s_plugin_connect_marketplace_attr` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `marketplace_attribute` varchar(255) NOT NULL UNIQUE,
              `local_attribute` varchar(255) NOT NULL UNIQUE,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;", "
            CREATE TABLE IF NOT EXISTS `s_plugin_connect_items` (
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
             `group_id` INT(11) NULL DEFAULT NULL,
             `is_main_variant` TINYINT(1) NULL DEFAULT NULL,
             `purchase_price_hash` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
             `offer_valid_until` int(10) NOT NULL,
             `stream` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
             PRIMARY KEY (`id`),
             UNIQUE KEY `article_detail_id` (`article_detail_id`),
             KEY `article_id` (`article_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;", "
            CREATE TABLE IF NOT EXISTS `sw_connect_shipping_rules` (
             `sr_id` int(11) NOT NULL AUTO_INCREMENT,
             `sr_group_id` int(11) unsigned DEFAULT NULL,
             `sr_country` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
             `sr_delivery_days` int(5) DEFAULT NULL,
             `sr_price` double DEFAULT NULL,
             `sr_zip_prefix` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
             PRIMARY KEY (`sr_id`),
             KEY `sr_group_id` (`sr_group_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci","
            CREATE TABLE IF NOT EXISTS `s_plugin_connect_categories` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `category_key` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
              `label` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
              `local_category_id` int(11) DEFAULT NULL,
              PRIMARY KEY (`id`),
              INDEX (`category_key`),
              UNIQUE KEY `scuk_category_key` (`category_key`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;", "
            CREATE TABLE IF NOT EXISTS `s_plugin_connect_product_to_categories` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `connect_category_id` int(11) NOT NULL,
              `articleID` int(11) NOT NULL,
              PRIMARY KEY (`id`),
              UNIQUE KEY `scuk_connect_category_id` (`connect_category_id`,`articleID`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;", "
            CREATE TABLE IF NOT EXISTS `s_plugin_connect_streams` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `stream_id` int(11) NOT NULL,
              `export_status` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
              `export_message` text COLLATE utf8_unicode_ci DEFAULT NULL,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
            ");

        foreach ($queries as $query) {
            Shopware()->Db()->exec($query);
        }
    }


    /**
     * Creates product, order and category attributes
     */
    private function createMyAttributes()
    {
        /** @var \Shopware\Components\Model\ModelManager $modelManager */
        $modelManager =Shopware()->Models();

        $modelManager->addAttribute(
            's_order_attributes',
            'connect', 'shop_id',
            'int(11)'
        );
        $modelManager->addAttribute(
            's_order_attributes',
            'connect', 'order_id',
            'int(11)'
        );

        $modelManager->addAttribute(
            's_categories_attributes',
            'connect', 'import_mapping',
            'text'
        );

        $modelManager->addAttribute(
            's_categories_attributes',
            'connect', 'export_mapping',
            'text'
        );

        $modelManager->addAttribute(
            's_categories_attributes',
            'connect', 'imported',
            'text'
        );

        $modelManager->addAttribute(
            's_media_attributes',
            'connect', 'hash',
            'varchar(255)'
        );

        $modelManager->addAttribute(
            's_premium_dispatch_attributes',
            'connect', 'allowed',
            'int(1)',
            true,
            1
        );

        $modelManager->addAttribute(
            's_articles_attributes',
            'connect', 'product_description',
            'text'
        );

        $modelManager->addAttribute(
            's_articles_prices_attributes',
            'connect', 'price',
            'double',
            true,
            0
        );

        $modelManager->addAttribute(
            's_core_customergroups_attributes',
            'connect', 'group',
            'int(1)'
        );

        $modelManager->addAttribute(
            's_articles_attributes',
            'connect', 'article_shipping',
            'varchar(1000)'
        );

        $modelManager->addAttribute(
            's_premium_dispatch_attributes',
            'connect', 'allowed',
            'int(1)',
            true,
            1
        );

        $modelManager->addAttribute(
            's_categories_attributes',
            'connect', 'imported_category',
            'int(1)',
            true
        );

        $modelManager->addAttribute(
            's_articles_attributes',
            'connect', 'mapped_category',
            'int(1)',
            true
        );

        $modelManager->addAttribute(
            's_articles_attributes',
            'connect', 'remote_unit',
            'varchar(32)',
            true
        );

        $modelManager->generateAttributeModels(array(
            's_articles_attributes',
            's_order_attributes',
            's_core_customergroups_attributes',
            's_articles_prices_attributes',
            's_premium_dispatch_attributes',
            's_categories_attributes',
            's_order_details_attributes',
            's_order_basket_attributes',
            's_media_attributes'
        ));
    }


    /**
     * Creates the configuration table. Existing configs will not be overwritten
     */
    public function populateConfigTable()
    {
        $this->registerCustomModels();

        $this->bootstrap->registerMyLibrary();
        $configComponent = $this->bootstrap->getConfigComponents();
        // when add/remove item in $configs array
        // open ConnectConfigTest.php and change tearDown function
        // for some reason shopware runs test during plugin installation
        $configs = array(
            'priceGroupForPriceExport' => array('EK', null, 'export'),
            'priceGroupForPurchasePriceExport' => array('EK', null, 'export'),
            'priceFieldForPriceExport' => array('price', null, 'export'),
            'priceFieldForPurchasePriceExport' => array('basePrice', null, 'export'),

            'detailProductNoIndex' => array('1', null, 'general'),
            'detailShopInfo' => array('1', null, 'general'),
            'checkoutShopInfo' => array('1', null, 'general'),
            'alternateDescriptionField' => array('a.descriptionLong', null, 'export'),
            'connectAttribute' => array('19', null, 'general'),
            'importImagesOnFirstImport' => array('0', null, 'import'),
            'autoUpdateProducts' => array('1', null, 'export'),
            'overwriteProductName' => array('1', null, 'import'),
            'overwriteProductPrice' => array('1', null, 'import'),
            'overwriteProductImage' => array('1', null, 'import'),
            'overwriteProductShortDescription' => array('1', null, 'import'),
            'overwriteProductLongDescription' => array('1', null, 'import'),
            'logRequest' => array('1', null, 'general'),
            'showShippingCostsSeparately' => array('0', null, 'general'),
            'articleImagesLimitImport' => array(5, null, 'import'),
        );

        foreach ($configs as $name => $values) {
            list($value, $shopId, $group) = $values;

            try {
                $configComponent->setConfig(
                    $name,
                    $configComponent->getConfig($name, $value, $shopId),
                    $shopId,
                    $group
                );
            } catch(\Exception $e) {
                // This may fail if the config table was not updated, yet.
                // The Updater will take care of this
            }

        }
    }

    /**
     * Import frontend snippets
     */
    public function importSnippets()
    {
        $sql = file_get_contents($this->bootstrap->Path() . 'Snippets/frontend.sql');
        Shopware()->Db()->exec($sql);
    }


    /**
     * Creates an engine element so that the connectProductDescription is displayed in the article
     */
    public function createEngineElement()
    {
        $repo = Shopware()->Models()->getRepository('Shopware\Models\Article\Element');
        $element = $repo->findOneBy(array('name' => 'connectProductDescription'));

        if (!$element) {
            $element = new Element();
            $element->setName('connectProductDescription');
            $element->setType('html');
            $element->setLabel('SC Beschreibung');
            $element->setTranslatable(1);
            $element->setHelp('Falls Sie die Langbeschreibung ihres Artikels in diesem Attribut-Feld pflegen, wird statt der Langbeschreibung der Inhalt dieses Feldes exportiert');

            Shopware()->Models()->persist($element);
            Shopware()->Models()->flush();
        }
    }


    /**
     * Creates a shopware Connect customer group - this can be used by the shop owner to manage the Connect product prices
     *
     * Logic is very simple here - if a group with the key 'SC' already exists, no new group is created
     */
    public function createConnectCustomerGroup()
    {
        $db = Shopware()->Db();
        $connectGroupAttributeId = $this->getConnectCustomerGroupId();
        if (!$this->connectCustomerGroupExists($connectGroupAttributeId)) {

            // Create Customer Group
            $db->insert(
                's_core_customergroups',
                [
                    'groupkey' => $this->getAvailableCustomerGroupName(),
                    'description' => "SC export",
                    'tax' => 0,
                    'taxinput' => 0,
                    'mode' => 0
                ]
            );

            $customerGroupID = $db->fetchOne('SELECT MAX(id) FROM s_core_customergroups');

            // Create Customer Group Attributes
            $db->insert(
                's_core_customergroups_attributes',
                [
                  'customerGroupID' => $customerGroupID,
                  'connect_group' => 1
                ]
            );
        }
    }

    private function getConnectCustomerGroupId()
    {
        $db = Shopware()->Db();

        return $db->fetchOne(
            'SELECT customerGroupID
            FROM `s_core_customergroups_attributes`
            WHERE connect_group = 1'
        );
    }

    private function connectCustomerGroupExists($attributeId)
    {
        $db = Shopware()->Db();
        $result = $db->fetchOne(
            'SELECT COUNT(*)
            FROM `s_core_customergroups`
            WHERE id = :id',
            [
                'id' => $attributeId
            ]
        );

        return !empty($result);
    }

    /**
     * Return a free customer group name. It will only check 5 groups - if all are used, probably the detection
     * of existing connectCustomerGroups is broken. Throw an exception then
     *
     * @return mixed
     * @throws \RuntimeException
     */
    private function getAvailableCustomerGroupName()
    {
        $names = array('SC', 'SWC', 'SWCONN', 'SC-1');

        $repo = $repo = Shopware()->Models()->getRepository('Shopware\Models\Customer\Group');
        foreach ($names as $name) {
            $model = $repo->findOneBy(array('key' => $name));
            if (is_null($model)) {
                return $name;
            }
        }

        throw new \RuntimeException('Could not find a free group name for the Shopware Connect customer group.Probably you need to delete an existing customer group created by Shopware Connect (SC, SWC, SWCONN, SC-1). Make sure, you really don\'t need it any more!'
        );
    }

    public function registerCustomModels()
    {
        Shopware()->Loader()->registerNamespace(
            'Shopware\CustomModels',
            $this->bootstrap->Path() . 'Models/'
        );
        Shopware()->ModelAnnotations()->addPaths(array(
            $this->bootstrap->Path() . 'Models/'
        ));
    }

    /**
     * Populates the dispatch attributes with entries for each dispatch type, so that
     * the connect attribute can be used
     */
    public function populateDispatchAttributes()
    {
        Shopware()->Db()->exec('
            INSERT IGNORE INTO `s_premium_dispatch_attributes` (`dispatchID`)
            SELECT `id` FROM `s_premium_dispatch`
        ');
    }

    /**
     * Generates connect payment attribute
     */
    public function generateConnectPaymentAttribute()
    {
        Shopware()->Models()->addAttribute(
            's_core_paymentmeans_attributes',
            'connect', 'is_allowed',
            'int(1)',
            true,
            1
        );

        Shopware()->Models()->generateAttributeModels(array(
            's_core_paymentmeans_attributes'
        ));

        Shopware()->Models()->regenerateProxies();
    }

    public function populateConnectPaymentAttribute()
    {
        Shopware()->Db()->exec('
            INSERT IGNORE INTO `s_core_paymentmeans_attributes` (`paymentmeanID`)
            SELECT `id` FROM `s_core_paymentmeans`
        ');
    }

    public function populatePaymentStates()
    {
        $states = array(
            'SC received',
            'SC requested',
            'SC instructed',
            'SC aborted',
            'SC timeout',
            'SC pending',
            'SC refunded',
            'SC loss',
            'SC error',
        );

        $query = Shopware()->Models()->getRepository('Shopware\Models\Order\Status')->createQueryBuilder('s');
        $query->select('MAX(s.id)');

        $maxId = $query->getQuery()->getOneOrNullResult();
        $currentId = 0;

        if (count($maxId) > 0) {
            foreach ($maxId as $id) {
                if ($id > $currentId) {
                    $currentId = $id;
                }
            }
        }

        foreach ($states as $name) {
            $isExists = Shopware()->Db()->query('
                SELECT `id` FROM `s_core_states`
                WHERE `description` = ?
                ', array($name)
            )->fetch();

            if ($isExists) {
                continue;
            }

            $currentId++;
            Shopware()->Db()->query('
                INSERT INTO `s_core_states`
                (`id`, `description`, `position`, `group`, `mail`)
                VALUES (?, ?, ?, ?, ?)
                ', array($currentId, $name, $currentId, 'payment', 0)
            );
        }
    }
}