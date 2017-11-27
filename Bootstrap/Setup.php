<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Bootstrap;

use Shopware\Bundle\AttributeBundle\Service\CrudService;
use Shopware\Models\Attribute\Configuration;
use Shopware\Models\Customer\Group;
use Shopware\Components\Model\ModelManager;
use Enlight_Components_Db_Adapter_Pdo_Mysql as Pdo;
use ShopwarePlugins\Connect\Components\Config;
use Shopware\Models\Order\Status;
use ShopwarePlugins\Connect\Components\Utils\ConnectOrderUtil;

/**
 * The setup class does the basic setup of the shopware Connect plugin. All operations should be implemented in a way
 * that they can also be run on update of the plugin
 *
 * Class Setup
 * @package ShopwarePlugins\Connect\Bootstrap
 */
class Setup
{
    /**
     * @var \Shopware_Plugins_Backend_SwagConnect_Bootstrap
     */
    protected $bootstrap;

    /**
     * @var Pdo
     */
    protected $db;

    /**
     * @var ModelManager
     */
    protected $modelManager;

    private $menu;

    /**
     * Setup constructor.
     * @param \Shopware_Plugins_Backend_SwagConnect_Bootstrap $bootstrap
     * @param ModelManager $modelManager
     * @param Pdo $db
     * @param Menu
     */
    public function __construct(
        \Shopware_Plugins_Backend_SwagConnect_Bootstrap $bootstrap,
        ModelManager $modelManager,
        Pdo $db,
        Menu $menu

    ) {
        $this->bootstrap = $bootstrap;
        $this->modelManager = $modelManager;
        $this->db = $db;
        $this->menu = $menu;
    }

    public function run($fullSetup)
    {
        $this->createMyEvents();
        $this->createMyTables();
        $this->createConfig();
        $this->createMyAttributes();
        $this->populateConfigTable();
        $this->importSnippets();
        $this->generateConnectPaymentAttribute();
        $this->populateDispatchAttributes();
        $this->populateConnectPaymentAttribute();
        $this->createConnectCustomerGroup();

        if ($fullSetup) {
            $this->createMyMenu();
            $this->populatePaymentStates();
            $this->populateOrderStates();
        }
    }

    private function createConfig()
    {
        $form = $this->bootstrap->Form();

        $form->setElement('text',
            'connectDebugHost',
            [
                'label' => 'Shopware Connect Host',
                'required' => false,
                'value'    => Config::MARKETPLACE_URL
            ]);
    }

    /**
     * Creates the plugin menu item
     */
    private function createMyMenu()
    {
        $this->menu->create();

        $sql = "INSERT IGNORE INTO `s_core_snippets` (`namespace`, `shopID`, `localeID`, `name`, `value`, `created`, `updated`) VALUES
            ('backend/index/view/main', 1, 1, 'Connect', 'Connect', '2016-03-17 18:32:48', '2016-03-17 18:32:48'),
            ('backend/index/view/main', 1, 2, 'Connect', 'Connect', '2016-03-17 18:32:48', '2016-03-17 18:32:48'),
            ('backend/index/view/main', 1, 1, 'Connect/Export', 'Produkte zu Connect', '2016-03-17 18:32:48', '2016-03-17 18:32:48'),
            ('backend/index/view/main', 1, 2, 'Connect/Export', 'Export', '2016-03-17 18:32:48', '2016-03-17 18:32:48'),
            ('backend/index/view/main', 1, 1, 'Connect/Settings', 'Einstellungen', '2016-03-17 18:32:48', '2016-03-17 18:32:48'),
            ('backend/index/view/main', 1, 2, 'Connect/Settings', 'Settings', '2016-03-17 18:32:48', '2016-03-17 18:32:48'),
            ('backend/index/view/main', 1, 1, 'Connect/Register', 'Einrichtung', '2016-03-17 18:32:48', '2016-03-17 18:32:48'),
            ('backend/index/view/main', 1, 2, 'Connect/Register', 'Register', '2016-03-17 18:32:48', '2016-03-17 18:32:48'),
            ('backend/index/view/main', 1, 1, 'Connect/Import', 'Produkte von Connect', '2016-03-17 18:32:48', '2016-03-17 18:32:48'),
            ('backend/index/view/main', 1, 2, 'Connect/Import', 'Import', '2016-03-17 18:32:48', '2016-03-17 18:32:48'),
            ('backend/index/view/main', 1, 1, 'Connect/OpenConnect', 'Login', '2016-03-17 18:32:48', '2016-03-17 18:32:48'),
            ('backend/index/view/main', 1, 2, 'Connect/OpenConnect', 'Login', '2016-03-17 18:32:48', '2016-03-17 18:32:48')

            ON DUPLICATE KEY UPDATE
              `namespace` = VALUES(`namespace`),
              `shopID` = VALUES(`shopID`),
              `name` = VALUES(`name`),
              `localeID` = VALUES(`localeID`),
              `value` = VALUES(`value`)
              ;";
        $this->db->exec($sql);
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

        $this->bootstrap->subscribeEvent(
            'Shopware_Console_Add_Command',
            'onConsoleAddCommand'
        );

        $connectImportImages = $this->db->fetchOne(
            'SELECT id FROM s_crontab WHERE `action` LIKE :action',
            ['action' => '%ShopwareConnectImportImages']
        );

        if (!$connectImportImages) {
            $this->bootstrap->createCronJob(
                'SwagConnect Import images',
                'ShopwareConnectImportImages',
                60 * 30,
                false
            );
        }

        $connectUpdateProducts = $this->db->fetchOne(
            'SELECT id FROM s_crontab WHERE `action` LIKE :action',
            ['action' => '%ShopwareConnectUpdateProducts']
        );

        if (!$connectUpdateProducts) {
            $this->bootstrap->createCronJob(
                'SwagConnect Update Products',
                'ShopwareConnectUpdateProducts',
                60 * 2,
                false
            );
        }

        $connectExportDynamicStreams = $this->db->fetchOne(
            'SELECT id FROM s_crontab WHERE `action` LIKE :action',
            ['action' => '%ConnectExportDynamicStreams']
        );

        if (!$connectExportDynamicStreams) {
            $this->bootstrap->createCronJob(
                'SwagConnect Export Dynamic Streams',
                'Shopware_CronJob_ConnectExportDynamicStreams',
                12 * 3600, //12hours
                true
            );
        }
    }

    /**
     * Create necessary tables
     */
    private function createMyTables()
    {
        $queries = ['
            CREATE TABLE IF NOT EXISTS `sw_connect_change` (
              `c_entity_id` varchar(64) NOT NULL,
              `c_operation` char(8) NOT NULL,
              `c_revision` decimal(20,10) NOT NULL,
              `c_payload` longblob,
              `changed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              UNIQUE KEY `c_revision` (`c_revision`),
              KEY `c_entity_id` (`c_entity_id`),
              INDEX `c_operation` (`c_operation`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;', '
           CREATE TABLE IF NOT EXISTS `sw_connect_data` (
              `d_key` varchar(32) NOT NULL,
              `d_value` varchar(256) NOT NULL,
              `changed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`d_key`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;', '
            CREATE TABLE IF NOT EXISTS `sw_connect_product` (
              `p_source_id` varchar(64) NOT NULL,
              `p_hash` varchar(64) NOT NULL,
              `changed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`p_source_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;', '
            CREATE TABLE IF NOT EXISTS `sw_connect_reservations` (
              `r_id` varchar(32) NOT NULL,
              `r_state` varchar(12) NOT NULL,
              `r_order` longblob NOT NULL,
              `changed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`r_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;', '
            CREATE TABLE IF NOT EXISTS `sw_connect_shop_config` (
              `s_shop` varchar(32) NOT NULL,
              `s_config` LONGBLOB NOT NULL,
              `changed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`s_shop`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;', '
            CREATE TABLE IF NOT EXISTS `s_plugin_connect_config` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `name` varchar(255) NOT NULL,
              `value` TEXT NOT NULL,
              `shopId` int(11) NULL,
              `groupName` varchar(255) NULL,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;', '
            CREATE TABLE IF NOT EXISTS `sw_connect_shipping_costs` (
              `sc_from_shop` VARCHAR(32) NOT NULL,
              `sc_to_shop` VARCHAR(32) NOT NULL,
              `sc_revision` VARCHAR(32) NOT NULL,
              `sc_shipping_costs` LONGBLOB NOT NULL,
              `sc_customer_costs` LONGBLOB NOT NULL,
              `changed` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`sc_from_shop`, `sc_to_shop`),
              INDEX (`sc_revision`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;', '
            CREATE TABLE IF NOT EXISTS `s_plugin_connect_log` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `isError` int(1) NOT NULL,
              `service` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
              `command` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
              `request` text COLLATE utf8_unicode_ci DEFAULT NULL,
              `response` text COLLATE utf8_unicode_ci DEFAULT NULL,
              `time` datetime NOT NULL,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;', '
            CREATE TABLE IF NOT EXISTS `s_plugin_connect_marketplace_attr` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `marketplace_attribute` varchar(255) NOT NULL UNIQUE,
              `local_attribute` varchar(255) NOT NULL UNIQUE,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;', "
            CREATE TABLE IF NOT EXISTS `s_plugin_connect_items` (
             `id` int(11) NOT NULL AUTO_INCREMENT,
             `article_id` int(11) unsigned DEFAULT NULL,
             `article_detail_id` int(11) unsigned DEFAULT NULL,
             `shop_id` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
             `source_id` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
             `export_status` text COLLATE utf8_unicode_ci,
             `export_message` text COLLATE utf8_unicode_ci,
             `exported` TINYINT(1) DEFAULT 0,
             `category` text COLLATE utf8_unicode_ci,
             `purchase_price` double DEFAULT NULL,
             `fixed_price` int(1) DEFAULT NULL,
             `free_delivery` int(1) DEFAULT NULL,
             `update_price` varchar(255) COLLATE utf8_unicode_ci DEFAULT 'inherit',
             `update_image` varchar(255) COLLATE utf8_unicode_ci DEFAULT 'inherit',
             `update_main_image` varchar(255) COLLATE utf8_unicode_ci DEFAULT 'inherit',
             `update_long_description` varchar(255) COLLATE utf8_unicode_ci DEFAULT 'inherit',
             `update_short_description` varchar(255) COLLATE utf8_unicode_ci DEFAULT 'inherit',
             `update_additional_description` varchar(255) COLLATE utf8_unicode_ci DEFAULT 'inherit',
             `update_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT 'inherit',
             `last_update` longtext COLLATE utf8_unicode_ci,
             `last_update_flag` int(11) DEFAULT NULL,
             `group_id` VARCHAR (64) NULL DEFAULT NULL,
             `is_main_variant` TINYINT(1) NULL DEFAULT NULL,
             `purchase_price_hash` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
             `offer_valid_until` int(10) NOT NULL,
             `stream` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
             `cron_update` TINYINT(1) NULL DEFAULT NULL,
             `revision` decimal(20,10) DEFAULT NULL,
             PRIMARY KEY (`id`),
             UNIQUE KEY `article_detail_id` (`article_detail_id`),
             KEY `article_id` (`article_id`),
             INDEX source_id (source_id, shop_id),
             INDEX group_id (group_id, shop_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;", '
            CREATE TABLE IF NOT EXISTS `sw_connect_shipping_rules` (
             `sr_id` int(11) NOT NULL AUTO_INCREMENT,
             `sr_group_id` int(11) unsigned DEFAULT NULL,
             `sr_country` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
             `sr_delivery_days` int(5) DEFAULT NULL,
             `sr_price` double DEFAULT NULL,
             `sr_zip_prefix` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
             PRIMARY KEY (`sr_id`),
             KEY `sr_group_id` (`sr_group_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci','
            CREATE TABLE IF NOT EXISTS `s_plugin_connect_categories` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `category_key` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
              `label` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
              `local_category_id` int(11) DEFAULT NULL,
              `shop_id` int(11) NULL,
              PRIMARY KEY (`id`),
              INDEX (`category_key`),
              UNIQUE KEY `scuk_connect_category_for_shop_id` (`category_key`,`shop_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;', '
            CREATE TABLE IF NOT EXISTS `s_plugin_connect_product_to_categories` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `connect_category_id` int(11) NOT NULL,
              `articleID` int(11) NOT NULL,
              PRIMARY KEY (`id`),
              INDEX article_id (articleID),
              UNIQUE KEY `scuk_connect_category_id` (`connect_category_id`,`articleID`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;', '
            CREATE TABLE IF NOT EXISTS `s_plugin_connect_streams` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `stream_id` int(11) NOT NULL,
              `export_status` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
              `export_message` text COLLATE utf8_unicode_ci DEFAULT NULL,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;',"
            CREATE TABLE IF NOT EXISTS `s_plugin_connect_streams_relation` (
                `stream_id` int(11) unsigned NOT NULL,
                `article_id` int(11) unsigned NOT NULL,
                `deleted` int(1) NOT NULL DEFAULT '0',
                UNIQUE KEY `stream_id` (`stream_id`,`article_id`),
                CONSTRAINT s_plugin_connect_streams_selection_fk_stream_id FOREIGN KEY (stream_id) REFERENCES s_product_streams (id) ON DELETE CASCADE,
                CONSTRAINT s_plugin_connect_streams_selection_fk_article_id FOREIGN KEY (article_id) REFERENCES s_articles (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;", '
            CREATE TABLE IF NOT EXISTS `s_plugin_connect_categories_to_local_categories` (
                `remote_category_id` int(11) NOT NULL,
                `local_category_id` int(11) unsigned NOT NULL,
                PRIMARY KEY (`remote_category_id`, `local_category_id`),
                CONSTRAINT s_plugin_connect_remote_categories_fk_remote_category_id FOREIGN KEY (remote_category_id) REFERENCES s_plugin_connect_categories (id) ON DELETE CASCADE,
                CONSTRAINT s_plugin_connect_remote_categories_fk_local_category_id FOREIGN KEY (local_category_id) REFERENCES s_categories (id) ON DELETE CASCADE
            ) ENGINE = InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
            '];

        foreach ($queries as $query) {
            $this->db->exec($query);
        }
    }

    public function getCrudService()
    {
        return $this->bootstrap->Application()->Container()->get('shopware_attribute.crud_service');
    }

    /**
     * Creates product, order and category attributes
     */
    private function createMyAttributes()
    {
        /** @var CrudService $crudService */
        $crudService = $this->getCrudService();

        $crudService->update(
            's_order_attributes',
            'connect_shop_id',
            'integer'
        );

        $crudService->update(
            's_order_attributes',
            'connect_order_id',
            'integer'
        );

        $crudService->update(
            's_categories_attributes',
            'connect_import_mapping',
            'text'
        );

        $crudService->update(
            's_categories_attributes',
            'connect_export_mapping',
            'text'
        );

        $crudService->update(
            's_categories_attributes',
            'connect_imported',
            'text'
        );

        $crudService->update(
            's_media_attributes',
            'connect_hash',
            'string'
        );

        $crudService->update(
            's_premium_dispatch_attributes',
            'connect_allowed',
            'boolean',
            [],
            null,
            false,
            1
        );

        $crudService->update(
            's_articles_attributes',
            'connect_product_description',
            'html',
            [
                'translatable' => 1,
                'displayInBackend' => 1,
                'label' => 'Connect Beschreibung'
            ]
        );

        $crudService->update(
            's_articles_prices_attributes',
            'connect_price',
            'float',
            [],
            null,
            false,
            0
        );

        $crudService->update(
            's_core_customergroups_attributes',
            'connect_group',
            'boolean'
        );

        $crudService->update(
            's_articles_attributes',
            'connect_article_shipping',
            'text'
        );

        $crudService->update(
            's_articles_attributes',
            'connect_reference',
            'string'
        );

        $crudService->update(
            's_articles_attributes',
            'connect_property_group',
            'string'
        );

        $crudService->update(
            's_categories_attributes',
            'connect_imported_category',
            'boolean'
        );

        $crudService->update(
            's_articles_attributes',
            'connect_mapped_category',
            'boolean'
        );

        $crudService->update(
            's_articles_attributes',
            'connect_remote_unit',
            'string'
        );

        $crudService->update(
            's_articles_supplier_attributes',
            'connect_is_remote',
            'boolean',
            [],
            null,
            false,
            0
        );

        $crudService->update(
            's_articles_img_attributes',
            'connect_detail_mapping_id',
            'integer'
        );

        $crudService->update(
            's_filter_attributes',
            'connect_is_remote',
            'boolean',
            [],
            null,
            false,
            0
        );

        $crudService->update(
            's_filter_options_attributes',
            'connect_is_remote',
            'boolean',
            [],
            null,
            false,
            0
        );

        $crudService->update(
            's_filter_values_attributes',
            'connect_is_remote',
            'boolean',
            [],
            null,
            false,
            0
        );

        $crudService->update(
            's_product_streams_attributes',
            'connect_is_remote',
            'boolean',
            [],
            null,
            false,
            0
        );

        $this->modelManager->generateAttributeModels([
            's_articles_attributes',
            's_articles_supplier_attributes',
            's_order_attributes',
            's_core_customergroups_attributes',
            's_articles_prices_attributes',
            's_premium_dispatch_attributes',
            's_categories_attributes',
            's_order_details_attributes',
            's_order_basket_attributes',
            's_articles_img_attributes',
            's_media_attributes',
            's_filter_attributes',
            's_filter_options_attributes',
            's_filter_values_attributes',
            's_product_streams_attributes',
        ]);
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
        $configs = [
            'priceGroupForPriceExport' => ['', null, 'export'],
            'priceGroupForPurchasePriceExport' => ['', null, 'export'],
            'priceFieldForPriceExport' => ['', null, 'export'],
            'priceFieldForPurchasePriceExport' => ['', null, 'export'],
            'excludeInactiveProducts' => ['1', null, 'export'],
            'detailProductNoIndex' => ['1', null, 'general'],
            'detailShopInfo' => ['1', null, 'general'],
            'checkoutShopInfo' => ['1', null, 'general'],
            'longDescriptionField' => ['1', null, 'export'],
            'importImagesOnFirstImport' => ['1', null, 'import'],
            'autoUpdateProducts' => ['1', null, 'export'],
            'overwriteProductName' => ['1', null, 'import'],
            'overwriteProductPrice' => ['1', null, 'import'],
            'overwriteProductImage' => ['1', null, 'import'],
            'overwriteProductMainImage' => ['1', null, 'import'],
            'overwriteProductShortDescription' => ['1', null, 'import'],
            'overwriteProductLongDescription' => ['1', null, 'import'],
            'overwriteProductAdditionalDescription' => ['1', null, 'import'],
            'logRequest' => ['1', null, 'general'],
            'showShippingCostsSeparately' => ['0', null, 'general'],
            'articleImagesLimitImport' => [5, null, 'import'],
            'updateOrderStatus' => ['0', null, 'import'],
        ];

        foreach ($configs as $name => $values) {
            list($value, $shopId, $group) = $values;

            try {
                $configComponent->setConfig(
                    $name,
                    $configComponent->getConfig($name, $value, $shopId),
                    $shopId,
                    $group
                );
            } catch (\Exception $e) {
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
        $this->db->exec($sql);
    }

    /**
     * Creates a shopware Connect customer group - this can be used by the shop owner to manage the Connect product prices
     *
     * Logic is very simple here - if a group with the key 'SC' already exists, no new group is created
     */
    public function createConnectCustomerGroup()
    {
        $connectGroupAttributeId = $this->getConnectCustomerGroupId();
        if (!$this->connectCustomerGroupExists($connectGroupAttributeId)) {

            // Create Customer Group
            $this->db->insert(
                's_core_customergroups',
                [
                    'groupkey' => $this->getAvailableCustomerGroupName(),
                    'description' => 'SC export',
                    'tax' => 0,
                    'taxinput' => 0,
                    'mode' => 0
                ]
            );

            $customerGroupID = $this->db->fetchOne('SELECT MAX(id) FROM s_core_customergroups');

            // Create Customer Group Attributes
            $this->db->insert(
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
        return $this->db->fetchOne(
            'SELECT customerGroupID
            FROM `s_core_customergroups_attributes`
            WHERE connect_group = 1'
        );
    }

    private function connectCustomerGroupExists($attributeId)
    {
        $result = $this->db->fetchOne(
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
     * @throws \RuntimeException
     * @return mixed
     */
    private function getAvailableCustomerGroupName()
    {
        $names = ['SC', 'SWC', 'SWCONN', 'SC-1'];

        $repo = $this->modelManager->getRepository('Shopware\Models\Customer\Group');
        foreach ($names as $name) {
            $model = $repo->findOneBy(['key' => $name]);
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
        Shopware()->ModelAnnotations()->addPaths([
            $this->bootstrap->Path() . 'Models/'
        ]);
    }

    /**
     * Populates the dispatch attributes with entries for each dispatch type, so that
     * the connect attribute can be used
     */
    public function populateDispatchAttributes()
    {
        $this->db->exec('
            INSERT IGNORE INTO `s_premium_dispatch_attributes` (`dispatchID`)
            SELECT `id` FROM `s_premium_dispatch`
        ');
    }

    /**
     * Generates connect payment attribute
     */
    public function generateConnectPaymentAttribute()
    {
        /** @var CrudService $crudService */
        $crudService = $this->getCrudService();

        $crudService->update(
            's_core_paymentmeans_attributes',
            'connect_is_allowed',
            'boolean',
            [],
            null,
            false,
            1
        );

        $this->modelManager->generateAttributeModels([
            's_core_paymentmeans_attributes'
        ]);

        $this->modelManager->regenerateProxies();
    }

    public function populateConnectPaymentAttribute()
    {
        $this->db->exec('
            INSERT IGNORE INTO `s_core_paymentmeans_attributes` (`paymentmeanID`)
            SELECT `id` FROM `s_core_paymentmeans`
        ');
    }

    public function populatePaymentStates()
    {
        $states = [
            'sc_received' => ' SC received',
            'sc_requested' => 'SC requested',
            'sc_initiated' => 'SC initiated',
            'sc_instructed' => 'SC instructed',
            'sc_aborted' => 'SC aborted',
            'sc_timeout' => 'SC timeout',
            'sc_pending' => 'SC pending',
            'sc_refunded' => 'SC refunded',
            'sc_verify' => 'SC verify',
            'sc_loss' => 'SC loss',
            'sc_error' => 'SC error',
        ];

        $this->populateStates($states, Status::GROUP_PAYMENT);
    }

    public function populateOrderStates()
    {
        $states = [
            ConnectOrderUtil::ORDER_STATUS_ERROR => 'SC error'
        ];

        $this->populateStates($states, Status::GROUP_STATE);
    }

    public function populateStates(array $states, $group)
    {
        $currentId = $this->getMaxStateId();

        foreach ($states as $name => $description) {
            $isExists = $this->db->query('
                SELECT `id` FROM `s_core_states`
                WHERE `name` = ? AND `group` = ?
                ', [$name, $group]
            )->fetch();

            if ($isExists) {
                continue;
            }

            ++$currentId;
            $this->db->query('
                INSERT INTO `s_core_states`
                (`id`, `name`, `description`, `position`, `group`, `mail`)
                VALUES (?, ?, ?, ?, ?, ?)
                ', [$currentId, $name, $description, $currentId, $group, 0]
            );
        }
    }

    /**
     * @return int
     */
    private function getMaxStateId()
    {
        $query = $this->modelManager->getRepository('Shopware\Models\Order\Status')->createQueryBuilder('s');
        $query->select('MAX(s.id)');
        $result = $query->getQuery()->getOneOrNullResult();

        if (count($result) > 0) {
            return (int) reset($result);
        }

        return 0;
    }
}
