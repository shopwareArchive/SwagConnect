<?php

namespace Shopware\Bepado\Bootstrap;

use Shopware\Models\Article\Element;
use Shopware\Models\Customer\Group;

/**
 * The setup class does the basic setup of the bepado plugin. All operations should be implemented in a way
 * that they can also be run on update of the plugin
 *
 * Class Setup
 * @package Shopware\Bepado\Bootstrap
 */
class Setup
{
    protected $bootstrap;

    public function __construct(\Shopware_Plugins_Backend_SwagBepado_Bootstrap $bootstrap)
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
        $this->generateBepadoPaymentAttribute();
        $this->populateDispatchAttributes();
        $this->populateBepadoPaymentAttribute();


        $this->createBepadoCustomerGroup();


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
        $bepadoItem = $this->bootstrap->Menu()->findOneBy(array('label' => $this->bootstrap->getLabel()));
        // check if bepado menu item exists
        if (!$bepadoItem) {
            $parent = $this->bootstrap->Menu()->findOneBy(array('label' => 'Marketing'));

            $this->bootstrap->createMenuItem(array(
                'label' => $this->bootstrap->getLabel(),
                'controller' => 'Bepado',
                'action' => 'Index',
                'class' => 'bepado-icon',
                'active' => 1,
                'parent' => $parent
            ));
        }
    }

    /**
     * Registers the bepado events. As we register all events on the fly, only the early
     * Enlight_Controller_Front_StartDispatch-Event is needed.
     */
    public function createMyEvents()
    {
        $this->bootstrap->subscribeEvent(
            'Enlight_Bootstrap_InitResource_BepadoSDK',
            'onInitResourceSDK'
        );

        $this->bootstrap->subscribeEvent(
            'Enlight_Controller_Front_DispatchLoopStartup',
            'onStartDispatch'
        );

        Shopware()->Db()->query(
            'DELETE FROM s_crontab WHERE `name` = :name AND `action` = :action',
            array('name' => 'SwagBepado', 'action' => 'importImages')
        );
        Shopware()->Db()->query(
            'DELETE FROM s_crontab WHERE `name` = :name AND `action` = :action',
            array('name' => 'SwagBepado', 'action' => 'Shopware_CronJob_ImportImages')
        );
        $this->bootstrap->createCronJob(
            'SwagBepado',
            'Shopware_CronJob_ImportImages',
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
              `value` TEXT NOT NULL,
              `shopId` int(11) NULL,
              `groupName` varchar(255) NULL,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;", "
            CREATE TABLE IF NOT EXISTS `bepado_shipping_costs` (
              `sc_from_shop` VARCHAR(32) NOT NULL,
              `sc_to_shop` VARCHAR(32) NOT NULL,
              `sc_revision` VARCHAR(32) NOT NULL,
              `sc_shipping_costs` LONGBLOB NOT NULL,
              `sc_customer_costs` LONGBLOB NOT NULL,
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
            CREATE TABLE IF NOT EXISTS `s_plugin_bepado_marketplace_attr` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `marketplace_attribute` varchar(255) NOT NULL UNIQUE,
              `local_attribute` varchar(255) NOT NULL UNIQUE,
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
             `group_id` INT(11) NULL DEFAULT NULL,
             `is_main_variant` TINYINT(1) NULL DEFAULT NULL,
             `purchase_price_hash` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
             `offer_valid_until` int(10) NOT NULL,
             PRIMARY KEY (`id`),
             UNIQUE KEY `article_detail_id` (`article_detail_id`),
             KEY `article_id` (`article_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;", "
            CREATE TABLE IF NOT EXISTS `bepado_shipping_rules` (
             `sr_id` int(11) NOT NULL AUTO_INCREMENT,
             `sr_group_id` int(11) unsigned DEFAULT NULL,
             `sr_country` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
             `sr_delivery_days` int(5) DEFAULT NULL,
             `sr_price` double DEFAULT NULL,
             `sr_zip_prefix` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
             PRIMARY KEY (`sr_id`),
             KEY `sr_group_id` (`sr_group_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
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
            true,
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

        $modelManager->addAttribute(
            's_core_customergroups_attributes',
            'bepado', 'group',
            'int(1)'
        );

        $modelManager->addAttribute(
            's_articles_attributes',
            'bepado', 'article_shipping',
            'varchar(1000)'
        );

        $modelManager->addAttribute(
            's_premium_dispatch_attributes',
            'bepado', 'allowed',
            'int(1)',
            true,
            1
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

        $configs = array(
            'priceGroupForPriceExport' => array('EK', null, 'export'),
            'priceGroupForPurchasePriceExport' => array('EK', null, 'export'),
            'priceFieldForPriceExport' => array('price', null, 'export'),
            'priceFieldForPurchasePriceExport' => array('basePrice', null, 'export'),

            'importCreateCategories' => array('1', null, null),
            'detailProductNoIndex' => array('1', null, 'general'),
            'detailShopInfo' => array('1', null, 'general'),
            'checkoutShopInfo' => array('1', null, 'general'),
            'alternateDescriptionField' => array('a.descriptionLong', null, 'export'),
            'bepadoAttribute' => array('19', null, 'general'),
            'importImagesOnFirstImport' => array('0', null, 'import'),
            'autoUpdateProducts' => array('1', null, 'export'),
            'overwriteProductName' => array('1', null, 'import'),
            'overwriteProductPrice' => array('1', null, 'import'),
            'overwriteProductImage' => array('1', null, 'import'),
            'overwriteProductShortDescription' => array('1', null, 'import'),
            'overwriteProductLongDescription' => array('1', null, 'import'),
            'logRequest' => array('1', null, 'general'),
            'shippingCostsPage' => array('6', null, 'general'),
            'articleImagesLimitImport' => array(10, null, 'import'),
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
     * Creates an engine element so that the bepadoProductDescription is displayed in the article
     */
    public function createEngineElement()
    {
        $repo = Shopware()->Models()->getRepository('Shopware\Models\Article\Element');
        $element = $repo->findOneBy(array('name' => 'bepadoProductDescription'));

        if (!$element) {
            $element = new Element();
            $element->setName('bepadoProductDescription');
            $element->setType('html');
            $element->setLabel('SC Beschreibung');
            $element->setTranslatable(1);
            $element->setHelp('Falls Sie die Langbeschreibung ihres Artikels in diesem Attribut-Feld pflegen, wird statt der Langbeschreibung der Inhalt dieses Feldes exportiert');

            Shopware()->Models()->persist($element);
            Shopware()->Models()->flush();
        }
    }


    /**
     * Creates a bepado customer group - this can be used by the shop owner to manage the bepado product prices
     *
     * Logic is very simple here - if a group with the key 'BEP' already exists, no new group is created
     */
    public function createBepadoCustomerGroup()
    {
        $repo = Shopware()->Models()->getRepository('Shopware\Models\Attribute\CustomerGroup');

        $bepadoGroupAttributeId = Shopware()->Db()->fetchOne(
            'SELECT id FROM s_core_customergroups_attributes WHERE bepado_group = 1'
        );

        /** @var \Shopware\Models\Attribute\CustomerGroup $model */
        $model = $repo->find($bepadoGroupAttributeId);

        $customerGroup = null;
        if ($model && $model->getCustomerGroup()) {
            $customerGroup = $model->getCustomerGroup();
        }

        if (!$customerGroup) {
            $customerGroup = new Group();
            $customerGroup->setKey($this->getAvailableCustomerGroupName());
            $customerGroup->setTax(false);
            $customerGroup->setTaxInput(false);
            $customerGroup->setMode(0);
            $customerGroup->setName('SC export');

            $attribute = new \Shopware\Models\Attribute\CustomerGroup();
            $attribute->setBepadoGroup(true);
            $customerGroup->setAttribute($attribute);


            Shopware()->Models()->persist($customerGroup);
            Shopware()->Models()->persist($attribute);
            Shopware()->Models()->flush();
        }
    }

    /**
     * Return a free customer group name. It will only check 5 groups - if all are used, probably the detection
     * of existing bepadoCustomerGroups is broken. Throw an exception then
     *
     * @return mixed
     * @throws \RuntimeException
     */
    private function getAvailableCustomerGroupName()
    {
        $names = array('BEP', 'BP', 'SWBEP', 'BEPA', 'BEP-1');

        $repo = $repo = Shopware()->Models()->getRepository('Shopware\Models\Customer\Group');
        foreach ($names as $name) {
            $model = $repo->findOneBy(array('key' => $name));
            if (is_null($model)) {
                return $name;
            }
        }

        throw new \RuntimeException('Could not find a free group name for the bepado customer group.Probably you need to delete an existing customer group created by bepado (BEP, bepado, BP, SW-BEP or SW-BEPADO). Make sure, you really don\'t need it any more!'
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
     * the bepado attribute can be used
     */
    public function populateDispatchAttributes()
    {
        Shopware()->Db()->exec('
            INSERT IGNORE INTO `s_premium_dispatch_attributes` (`dispatchID`)
            SELECT `id` FROM `s_premium_dispatch`
        ');
    }

    /**
     * Generates bepado payment attribute
     */
    public function generateBepadoPaymentAttribute()
    {
        Shopware()->Models()->addAttribute(
            's_core_paymentmeans_attributes',
            'bepado', 'is_allowed',
            'int(1)',
            true,
            1
        );

        Shopware()->Models()->generateAttributeModels(array(
            's_core_paymentmeans_attributes'
        ));

        Shopware()->Models()->regenerateProxies();
    }

    public function populateBepadoPaymentAttribute()
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